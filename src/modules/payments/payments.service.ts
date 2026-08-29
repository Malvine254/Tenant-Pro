import {
  BadRequestException,
  ForbiddenException,
  Injectable,
  Logger,
  NotFoundException,
} from '@nestjs/common';
import { InvoiceStatus, Prisma, PaymentStatus, RoleName, TransactionType } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { InitiatePaymentDto } from './dto/initiate-payment.dto';
import { MpesaCallbackDto } from './dto/mpesa-callback.dto';
import { MpesaService } from './mpesa.service';
import { FirebaseService } from '../firebase/firebase.service';

@Injectable()
export class PaymentsService {
  private readonly logger = new Logger(PaymentsService.name);

  constructor(
    private readonly prisma: PrismaService,
    private readonly mpesa: MpesaService,
    private readonly firebase: FirebaseService,
  ) {}

  // ---------------------------------------------------------------------------
  // Initiate STK Push
  // ---------------------------------------------------------------------------

  async initiateStkPush(actorUserId: string, actorRole: RoleName, dto: InitiatePaymentDto) {
    const requestedIds = [...new Set(dto.invoiceIds?.length ? dto.invoiceIds : [dto.invoiceId])]
      .filter((id): id is string => Boolean(id));
    if (requestedIds.length === 0) {
      throw new BadRequestException('At least one invoice is required');
    }

    const foundInvoices = await this.prisma.invoice.findMany({
      where: { id: { in: requestedIds } },
      include: { tenant: true },
    });
    if (foundInvoices.length !== requestedIds.length) {
      throw new NotFoundException('One or more invoices were not found');
    }

    const byId = new Map(foundInvoices.map((item) => [item.id, item]));
    const invoices = requestedIds.map((id) => byId.get(id)!);
    const invoice = invoices[0];

    for (const item of invoices) {
      if (item.status === InvoiceStatus.PAID || item.status === InvoiceStatus.CANCELLED) {
        throw new BadRequestException(`Invoice ${item.id} is already ${item.status}`);
      }
      if (actorRole === RoleName.TENANT && item.userId !== actorUserId) {
        throw new ForbiddenException('You can only pay your own invoices');
      }
    }

    // Access control – tenant can only pay their own invoice
    // Access for every invoice was checked above.

    // ── Partial-payment balance check ────────────────────────────────────
    const balances = invoices.map((item) => ({
      invoiceId: item.id,
      amount: Number((Number(item.totalAmount) - Number(item.paidAmount ?? 0)).toFixed(2)),
    }));
    const remaining = Number(balances.reduce((sum, item) => sum + item.amount, 0).toFixed(2));

    if (balances.some((item) => item.amount <= 0)) {
      throw new BadRequestException('One or more invoices are already fully paid');
    }

    if (dto.amount !== undefined && dto.amount > remaining) {
      throw new BadRequestException(
        `Payment amount ${dto.amount} exceeds remaining balance of ${remaining}`,
      );
    }

    const amount = dto.amount !== undefined ? dto.amount : remaining;
    const allocations: Array<{ invoiceId: string; amount: number }> = [];
    let amountToAllocate = amount;
    for (const balance of balances) {
      if (amountToAllocate <= 0) break;
      const allocated = Number(Math.min(balance.amount, amountToAllocate).toFixed(2));
      allocations.push({ invoiceId: balance.invoiceId, amount: allocated });
      amountToAllocate = Number((amountToAllocate - allocated).toFixed(2));
    }
    // ────────────────────────────────────────────────────────────────────────

    // Create a payment record in INITIATED state before contacting M-Pesa
    const payment = await this.prisma.payment.create({
      data: {
        invoiceId: invoice.id,
        tenantId: invoice.tenantId,
        userId: invoice.userId,
        amount,
        method: 'MPESA',
        status: PaymentStatus.INITIATED,
        phoneNumber: dto.phoneNumber,
      },
    });

    // Store the request transaction for traceability
    await this.prisma.transaction.create({
      data: {
        paymentId: payment.id,
        type: TransactionType.STK_PUSH,
        provider: 'MPESA',
        amount,
        rawPayload: { invoiceAllocations: allocations },
        isValid: false,
      },
    });

    try {
      const stkResult = await this.mpesa.stkPush({
        phoneNumber: dto.phoneNumber,
        amount,
        accountReference: requestedIds.length > 1
          ? `BILLS-${invoice.id.substring(0, 6)}`.toUpperCase()
          : invoice.id.substring(0, 12).toUpperCase(),
        transactionDesc: requestedIds.length > 1
          ? `Payment for ${requestedIds.length} invoices`
          : `Payment for invoice ${invoice.id}`,
      });

      // Persist both M-Pesa request IDs on the payment row
      const updatedPayment = await this.prisma.payment.update({
        where: { id: payment.id },
        data: {
          status: PaymentStatus.PENDING,
          mpesaRequestId: stkResult.MerchantRequestID,
          mpesaCheckoutRequestId: stkResult.CheckoutRequestID,
        },
      });

      return {
        message: 'STK Push sent. Awaiting customer confirmation.',
        paymentId: updatedPayment.id,
        checkoutRequestId: stkResult.CheckoutRequestID,
        customerMessage: stkResult.CustomerMessage,
      };
    } catch (error) {
      // Mark payment as FAILED if STK Push errors out
      await this.prisma.payment.update({
        where: { id: payment.id },
        data: { status: PaymentStatus.FAILED },
      });
      throw error;
    }
  }

  // ---------------------------------------------------------------------------
  // Handle M-Pesa Callback
  // ---------------------------------------------------------------------------

  async handleCallback(payload: MpesaCallbackDto) {
    const cb = payload.Body.stkCallback;
    const { MerchantRequestID, CheckoutRequestID, ResultCode, ResultDesc } = cb;

    this.logger.log(
      `M-Pesa callback received – CheckoutRequestID: ${CheckoutRequestID}, ResultCode: ${ResultCode}`,
    );

    const payment = await this.prisma.payment.findUnique({
      where: { mpesaCheckoutRequestId: CheckoutRequestID },
      include: {
        invoice: true,
        transactions: {
          where: { type: TransactionType.STK_PUSH },
          orderBy: { createdAt: 'desc' },
          take: 1,
        },
      },
    });

    if (!payment) {
      this.logger.warn(
        `No payment found for CheckoutRequestID: ${CheckoutRequestID}. Ignoring callback.`,
      );
      return { ResultCode: 0, ResultDesc: 'Accepted' };
    }

    // Safaricom can retry callbacks. Never allocate the same receipt twice.
    if (payment.status === PaymentStatus.SUCCESS || payment.status === PaymentStatus.FAILED) {
      return { ResultCode: 0, ResultDesc: 'Accepted' };
    }

    const isSuccess = ResultCode === 0;

    // Extract metadata from successful callback
    let receiptNumber: string | undefined;
    let transactionAmount: number | undefined;

    if (isSuccess && cb.CallbackMetadata?.Item) {
      for (const item of cb.CallbackMetadata.Item) {
        if (item.Name === 'MpesaReceiptNumber') receiptNumber = String(item.Value ?? '');
        if (item.Name === 'Amount') transactionAmount = Number(item.Value ?? 0);
      }
    }

    // Store the callback transaction record
    await this.prisma.transaction.create({
      data: {
        paymentId: payment.id,
        externalReference: receiptNumber ?? `${MerchantRequestID}-${ResultCode}`,
        type: TransactionType.CALLBACK,
        provider: 'MPESA',
        resultCode: String(ResultCode),
        resultDescription: ResultDesc,
        amount: transactionAmount ?? Number(payment.amount),
        rawPayload: payload as unknown as Prisma.InputJsonValue,
        isValid: isSuccess,
        processedAt: new Date(),
      },
    });

    if (isSuccess) {
      const now = new Date();

      // Re-fetch to get latest paidAmount (may have changed since callback arrived)
      const requestPayload = payment.transactions[0]?.rawPayload as {
        invoiceAllocations?: Array<{ invoiceId?: unknown; amount?: unknown }>;
      } | null;
      const storedAllocations = requestPayload?.invoiceAllocations
        ?.filter((item) => typeof item.invoiceId === 'string' && Number(item.amount) > 0)
        .map((item) => ({ invoiceId: String(item.invoiceId), amount: Number(item.amount) }));
      const requestedAllocations = storedAllocations?.length
        ? storedAllocations
        : [{ invoiceId: payment.invoiceId, amount: Number(payment.amount) }];
      const allocationInvoices = await this.prisma.invoice.findMany({
        where: { id: { in: requestedAllocations.map((item) => item.invoiceId) } },
      });
      const allocationInvoiceById = new Map(allocationInvoices.map((item) => [item.id, item]));
      const amountPaidNow = transactionAmount ?? Number(payment.amount);
      let actualAmountRemaining = amountPaidNow;
      const invoiceUpdates: Prisma.PrismaPromise<unknown>[] = [];

      for (const allocation of requestedAllocations) {
        const currentInvoice = allocationInvoiceById.get(allocation.invoiceId);
        if (!currentInvoice) continue;
        const invoiceTotal = Number(currentInvoice.totalAmount);
        const currentPaid = Number(currentInvoice.paidAmount ?? 0);
        const currentBalance = Math.max(0, invoiceTotal - currentPaid);
        const allocatedNow = Number(
          Math.min(allocation.amount, actualAmountRemaining, currentBalance).toFixed(2),
        );
        if (allocatedNow <= 0) continue;
        const newPaidAmount = Number(
          Math.min(invoiceTotal, currentPaid + allocatedNow).toFixed(2),
        );
        invoiceUpdates.push(
          this.prisma.invoice.update({
            where: { id: allocation.invoiceId },
            data: {
              paidAmount: newPaidAmount,
              ...(newPaidAmount >= invoiceTotal
                ? { status: InvoiceStatus.PAID, paidAt: now }
                : {}),
            },
          }),
        );
        actualAmountRemaining = Number((actualAmountRemaining - allocatedNow).toFixed(2));
      }

      await this.prisma.$transaction([
        // Update payment row
        this.prisma.payment.update({
          where: { id: payment.id },
          data: {
            status: PaymentStatus.SUCCESS,
            mpesaReceiptNumber: receiptNumber,
            paidAt: now,
          },
        }),
        ...invoiceUpdates,
      ]);

      this.logger.log(
        `Payment ${payment.id} succeeded – Receipt: ${receiptNumber ?? 'N/A'} | ` +
        `allocated ${amountPaidNow} across ${invoiceUpdates.length} invoice(s)`,
      );

      const successUser = await this.prisma.user.findUnique({
        where: { id: payment.userId },
        select: { fcmToken: true },
      });
      if (successUser?.fcmToken) {
        void this.firebase.sendPush(
          successUser.fcmToken,
          'Payment Received',
          `Your payment of KES ${amountPaidNow.toLocaleString()} was successful. Receipt: ${receiptNumber ?? 'N/A'}.`,
          { type: 'PAYMENT', invoiceId: payment.invoiceId },
        );
      }
    } else {
      await this.prisma.payment.update({
        where: { id: payment.id },
        data: { status: PaymentStatus.FAILED },
      });

      this.logger.warn(`Payment ${payment.id} failed – ${ResultDesc}`);

      const failedUser = await this.prisma.user.findUnique({
        where: { id: payment.userId },
        select: { fcmToken: true },
      });
      if (failedUser?.fcmToken) {
        void this.firebase.sendPush(
          failedUser.fcmToken,
          'Payment Failed',
          `Your payment could not be completed. ${ResultDesc}`,
          { type: 'PAYMENT', invoiceId: payment.invoiceId },
        );
      }
    }

    // M-Pesa expects this exact JSON acknowledge response
    return { ResultCode: 0, ResultDesc: 'Accepted' };
  }

  // ---------------------------------------------------------------------------
  // Read helpers (tenant-accessible payment history)
  // ---------------------------------------------------------------------------

  async getPayments(actorUserId: string, actorRole: RoleName) {
    const where = actorRole === RoleName.ADMIN
      ? {}
      : actorRole === RoleName.LANDLORD
        ? { invoice: { unit: { property: { landlordId: actorUserId } } } }
        : { userId: actorUserId };

    return this.prisma.payment.findMany({
      where,
      include: {
        invoice: true,
        transactions: { orderBy: { createdAt: 'desc' } },
      },
      orderBy: { createdAt: 'desc' },
    });
  }

  async getPaymentsByInvoice(actorUserId: string, actorRole: RoleName, invoiceId: string) {
    const invoice = await this.prisma.invoice.findUnique({
      where: { id: invoiceId },
    });

    if (!invoice) throw new NotFoundException('Invoice not found');

    if (actorRole === RoleName.TENANT && invoice.userId !== actorUserId) {
      throw new ForbiddenException('You can only view your own payment records');
    }

    return this.prisma.payment.findMany({
      where: { invoiceId },
      include: { transactions: { orderBy: { createdAt: 'desc' } } },
      orderBy: { createdAt: 'desc' },
    });
  }
}

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

@Injectable()
export class PaymentsService {
  private readonly logger = new Logger(PaymentsService.name);

  constructor(
    private readonly prisma: PrismaService,
    private readonly mpesa: MpesaService,
  ) {}

  // ---------------------------------------------------------------------------
  // Initiate STK Push
  // ---------------------------------------------------------------------------

  async initiateStkPush(actorUserId: string, actorRole: RoleName, dto: InitiatePaymentDto) {
    const invoice = await this.prisma.invoice.findUnique({
      where: { id: dto.invoiceId },
      include: {
        tenant: true,
      },
    });

    if (!invoice) {
      throw new NotFoundException('Invoice not found');
    }

    if (invoice.status === InvoiceStatus.PAID || invoice.status === InvoiceStatus.CANCELLED) {
      throw new BadRequestException(`Invoice is already ${invoice.status}`);
    }

    // Access control – tenant can only pay their own invoice
    if (actorRole === RoleName.TENANT && invoice.userId !== actorUserId) {
      throw new ForbiddenException('You can only pay your own invoices');
    }

    // ── Partial-payment balance check ────────────────────────────────────
    const totalAmount  = Number(invoice.totalAmount);
    const paidAmount   = Number(invoice.paidAmount ?? 0);
    const remaining    = Number((totalAmount - paidAmount).toFixed(2));

    if (remaining <= 0) {
      throw new BadRequestException('Invoice is already fully paid');
    }

    if (dto.amount !== undefined && dto.amount > remaining) {
      throw new BadRequestException(
        `Payment amount ${dto.amount} exceeds remaining balance of ${remaining}`,
      );
    }

    const amount = dto.amount !== undefined ? dto.amount : remaining;
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
        isValid: false,
      },
    });

    try {
      const stkResult = await this.mpesa.stkPush({
        phoneNumber: dto.phoneNumber,
        amount,
        accountReference: invoice.id.substring(0, 12).toUpperCase(),
        transactionDesc: `Payment for invoice ${invoice.id}`,
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
      include: { invoice: true },
    });

    if (!payment) {
      this.logger.warn(
        `No payment found for CheckoutRequestID: ${CheckoutRequestID}. Ignoring callback.`,
      );
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
      const freshInvoice = await this.prisma.invoice.findUnique({
        where: { id: payment.invoiceId },
      });

      const amountPaidNow   = transactionAmount ?? Number(payment.amount);
      const prevPaid        = Number(freshInvoice?.paidAmount ?? 0);
      const newPaidAmount   = Number((prevPaid + amountPaidNow).toFixed(2));
      const invoiceTotal    = Number(freshInvoice?.totalAmount ?? '0');
      const isFullyPaid     = newPaidAmount >= invoiceTotal;

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
        // Update invoice paidAmount; only flip status to PAID when fully settled
        this.prisma.invoice.update({
          where: { id: payment.invoiceId },
          data: {
            paidAmount: newPaidAmount,
            ...(isFullyPaid
              ? { status: InvoiceStatus.PAID, paidAt: now }
              : {}),
          },
        }),
      ]);

      this.logger.log(
        `Payment ${payment.id} succeeded – Receipt: ${receiptNumber ?? 'N/A'} | ` +
        `paidAmount: ${newPaidAmount}/${invoiceTotal} (${isFullyPaid ? 'FULLY PAID' : 'PARTIAL'})`,
      );
    } else {
      await this.prisma.payment.update({
        where: { id: payment.id },
        data: { status: PaymentStatus.FAILED },
      });

      this.logger.warn(`Payment ${payment.id} failed – ${ResultDesc}`);
    }

    // M-Pesa expects this exact JSON acknowledge response
    return { ResultCode: 0, ResultDesc: 'Accepted' };
  }

  // ---------------------------------------------------------------------------
  // Read helpers (tenant-accessible payment history)
  // ---------------------------------------------------------------------------

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

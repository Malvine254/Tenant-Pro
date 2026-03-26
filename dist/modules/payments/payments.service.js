"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
var PaymentsService_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.PaymentsService = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const prisma_service_1 = require("../../prisma/prisma.service");
const mpesa_service_1 = require("./mpesa.service");
let PaymentsService = PaymentsService_1 = class PaymentsService {
    constructor(prisma, mpesa) {
        this.prisma = prisma;
        this.mpesa = mpesa;
        this.logger = new common_1.Logger(PaymentsService_1.name);
    }
    async initiateStkPush(actorUserId, actorRole, dto) {
        const invoice = await this.prisma.invoice.findUnique({
            where: { id: dto.invoiceId },
            include: {
                tenant: true,
            },
        });
        if (!invoice) {
            throw new common_1.NotFoundException('Invoice not found');
        }
        if (invoice.status === client_1.InvoiceStatus.PAID || invoice.status === client_1.InvoiceStatus.CANCELLED) {
            throw new common_1.BadRequestException(`Invoice is already ${invoice.status}`);
        }
        if (actorRole === client_1.RoleName.TENANT && invoice.userId !== actorUserId) {
            throw new common_1.ForbiddenException('You can only pay your own invoices');
        }
        const totalAmount = Number(invoice.totalAmount);
        const paidAmount = Number(invoice.paidAmount ?? 0);
        const remaining = Number((totalAmount - paidAmount).toFixed(2));
        if (remaining <= 0) {
            throw new common_1.BadRequestException('Invoice is already fully paid');
        }
        if (dto.amount !== undefined && dto.amount > remaining) {
            throw new common_1.BadRequestException(`Payment amount ${dto.amount} exceeds remaining balance of ${remaining}`);
        }
        const amount = dto.amount !== undefined ? dto.amount : remaining;
        const payment = await this.prisma.payment.create({
            data: {
                invoiceId: invoice.id,
                tenantId: invoice.tenantId,
                userId: invoice.userId,
                amount,
                method: 'MPESA',
                status: client_1.PaymentStatus.INITIATED,
                phoneNumber: dto.phoneNumber,
            },
        });
        await this.prisma.transaction.create({
            data: {
                paymentId: payment.id,
                type: client_1.TransactionType.STK_PUSH,
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
            const updatedPayment = await this.prisma.payment.update({
                where: { id: payment.id },
                data: {
                    status: client_1.PaymentStatus.PENDING,
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
        }
        catch (error) {
            await this.prisma.payment.update({
                where: { id: payment.id },
                data: { status: client_1.PaymentStatus.FAILED },
            });
            throw error;
        }
    }
    async handleCallback(payload) {
        const cb = payload.Body.stkCallback;
        const { MerchantRequestID, CheckoutRequestID, ResultCode, ResultDesc } = cb;
        this.logger.log(`M-Pesa callback received – CheckoutRequestID: ${CheckoutRequestID}, ResultCode: ${ResultCode}`);
        const payment = await this.prisma.payment.findUnique({
            where: { mpesaCheckoutRequestId: CheckoutRequestID },
            include: { invoice: true },
        });
        if (!payment) {
            this.logger.warn(`No payment found for CheckoutRequestID: ${CheckoutRequestID}. Ignoring callback.`);
            return { ResultCode: 0, ResultDesc: 'Accepted' };
        }
        const isSuccess = ResultCode === 0;
        let receiptNumber;
        let transactionAmount;
        if (isSuccess && cb.CallbackMetadata?.Item) {
            for (const item of cb.CallbackMetadata.Item) {
                if (item.Name === 'MpesaReceiptNumber')
                    receiptNumber = String(item.Value ?? '');
                if (item.Name === 'Amount')
                    transactionAmount = Number(item.Value ?? 0);
            }
        }
        await this.prisma.transaction.create({
            data: {
                paymentId: payment.id,
                externalReference: receiptNumber ?? `${MerchantRequestID}-${ResultCode}`,
                type: client_1.TransactionType.CALLBACK,
                provider: 'MPESA',
                resultCode: String(ResultCode),
                resultDescription: ResultDesc,
                amount: transactionAmount ?? Number(payment.amount),
                rawPayload: payload,
                isValid: isSuccess,
                processedAt: new Date(),
            },
        });
        if (isSuccess) {
            const now = new Date();
            const freshInvoice = await this.prisma.invoice.findUnique({
                where: { id: payment.invoiceId },
            });
            const amountPaidNow = transactionAmount ?? Number(payment.amount);
            const prevPaid = Number(freshInvoice?.paidAmount ?? 0);
            const newPaidAmount = Number((prevPaid + amountPaidNow).toFixed(2));
            const invoiceTotal = Number(freshInvoice?.totalAmount ?? '0');
            const isFullyPaid = newPaidAmount >= invoiceTotal;
            await this.prisma.$transaction([
                this.prisma.payment.update({
                    where: { id: payment.id },
                    data: {
                        status: client_1.PaymentStatus.SUCCESS,
                        mpesaReceiptNumber: receiptNumber,
                        paidAt: now,
                    },
                }),
                this.prisma.invoice.update({
                    where: { id: payment.invoiceId },
                    data: {
                        paidAmount: newPaidAmount,
                        ...(isFullyPaid
                            ? { status: client_1.InvoiceStatus.PAID, paidAt: now }
                            : {}),
                    },
                }),
            ]);
            this.logger.log(`Payment ${payment.id} succeeded – Receipt: ${receiptNumber ?? 'N/A'} | ` +
                `paidAmount: ${newPaidAmount}/${invoiceTotal} (${isFullyPaid ? 'FULLY PAID' : 'PARTIAL'})`);
        }
        else {
            await this.prisma.payment.update({
                where: { id: payment.id },
                data: { status: client_1.PaymentStatus.FAILED },
            });
            this.logger.warn(`Payment ${payment.id} failed – ${ResultDesc}`);
        }
        return { ResultCode: 0, ResultDesc: 'Accepted' };
    }
    async getPaymentsByInvoice(actorUserId, actorRole, invoiceId) {
        const invoice = await this.prisma.invoice.findUnique({
            where: { id: invoiceId },
        });
        if (!invoice)
            throw new common_1.NotFoundException('Invoice not found');
        if (actorRole === client_1.RoleName.TENANT && invoice.userId !== actorUserId) {
            throw new common_1.ForbiddenException('You can only view your own payment records');
        }
        return this.prisma.payment.findMany({
            where: { invoiceId },
            include: { transactions: { orderBy: { createdAt: 'desc' } } },
            orderBy: { createdAt: 'desc' },
        });
    }
};
exports.PaymentsService = PaymentsService;
exports.PaymentsService = PaymentsService = PaymentsService_1 = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService,
        mpesa_service_1.MpesaService])
], PaymentsService);
//# sourceMappingURL=payments.service.js.map
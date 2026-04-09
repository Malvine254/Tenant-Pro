import { Prisma, RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { InitiatePaymentDto } from './dto/initiate-payment.dto';
import { MpesaCallbackDto } from './dto/mpesa-callback.dto';
import { MpesaService } from './mpesa.service';
export declare class PaymentsService {
    private readonly prisma;
    private readonly mpesa;
    private readonly logger;
    constructor(prisma: PrismaService, mpesa: MpesaService);
    initiateStkPush(actorUserId: string, actorRole: RoleName, dto: InitiatePaymentDto): Promise<{
        message: string;
        paymentId: string;
        checkoutRequestId: string;
        customerMessage: string;
    }>;
    handleCallback(payload: MpesaCallbackDto): Promise<{
        ResultCode: number;
        ResultDesc: string;
    }>;
    getPaymentsByInvoice(actorUserId: string, actorRole: RoleName, invoiceId: string): Promise<({
        transactions: {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            type: import(".prisma/client").$Enums.TransactionType;
            amount: Prisma.Decimal | null;
            paymentId: string;
            externalReference: string | null;
            provider: string;
            resultCode: string | null;
            resultDescription: string | null;
            rawPayload: Prisma.JsonValue | null;
            isValid: boolean;
            processedAt: Date | null;
        }[];
    } & {
        id: string;
        phoneNumber: string | null;
        createdAt: Date;
        updatedAt: Date;
        userId: string;
        status: import(".prisma/client").$Enums.PaymentStatus;
        tenantId: string;
        amount: Prisma.Decimal;
        paidAt: Date | null;
        invoiceId: string;
        method: string;
        mpesaRequestId: string | null;
        mpesaCheckoutRequestId: string | null;
        mpesaReceiptNumber: string | null;
    })[]>;
}

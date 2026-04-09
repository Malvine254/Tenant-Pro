import { RoleName } from '@prisma/client';
import { MpesaCallbackDto } from './dto/mpesa-callback.dto';
import { InitiatePaymentDto } from './dto/initiate-payment.dto';
import { PaymentsService } from './payments.service';
type AuthenticatedRequest = {
    user: {
        userId: string;
        role: RoleName;
        phoneNumber: string;
    };
};
export declare class PaymentsController {
    private readonly paymentsService;
    constructor(paymentsService: PaymentsService);
    initiatePayment(req: AuthenticatedRequest, dto: InitiatePaymentDto): Promise<{
        message: string;
        paymentId: string;
        checkoutRequestId: string;
        customerMessage: string;
    }>;
    mpesaCallback(body: MpesaCallbackDto): Promise<{
        ResultCode: number;
        ResultDesc: string;
    }>;
    getPaymentsByInvoice(req: AuthenticatedRequest, invoiceId: string): Promise<({
        transactions: {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            type: import(".prisma/client").$Enums.TransactionType;
            amount: import("@prisma/client/runtime/library").Decimal | null;
            paymentId: string;
            externalReference: string | null;
            provider: string;
            resultCode: string | null;
            resultDescription: string | null;
            rawPayload: import("@prisma/client/runtime/library").JsonValue | null;
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
        amount: import("@prisma/client/runtime/library").Decimal;
        paidAt: Date | null;
        invoiceId: string;
        method: string;
        mpesaRequestId: string | null;
        mpesaCheckoutRequestId: string | null;
        mpesaReceiptNumber: string | null;
    })[]>;
}
export {};

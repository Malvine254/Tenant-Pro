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
            amount: import("@prisma/client/runtime/library").Decimal | null;
            id: string;
            createdAt: Date;
            updatedAt: Date;
            type: import(".prisma/client").$Enums.TransactionType;
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
        status: import(".prisma/client").$Enums.PaymentStatus;
        amount: import("@prisma/client/runtime/library").Decimal;
        id: string;
        invoiceId: string;
        tenantId: string;
        userId: string;
        method: string;
        phoneNumber: string | null;
        mpesaRequestId: string | null;
        mpesaCheckoutRequestId: string | null;
        mpesaReceiptNumber: string | null;
        paidAt: Date | null;
        createdAt: Date;
        updatedAt: Date;
    })[]>;
}
export {};

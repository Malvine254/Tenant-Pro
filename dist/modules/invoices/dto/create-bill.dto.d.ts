import { BillingType } from '@prisma/client';
export declare class CreateBillDto {
    tenantId: string;
    billingType: BillingType;
    amount: number;
    dueDate: string;
    periodMonth: number;
    periodYear: number;
}

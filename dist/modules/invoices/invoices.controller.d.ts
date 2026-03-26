import { RoleName } from '@prisma/client';
import { ApplyPenaltiesDto } from './dto/apply-penalties.dto';
import { CreateBillDto } from './dto/create-bill.dto';
import { GenerateRentInvoicesDto } from './dto/generate-rent-invoices.dto';
import { TenantIdParamDto } from './dto/tenant-id-param.dto';
import { InvoicesService } from './invoices.service';
type AuthenticatedRequest = {
    user: {
        userId: string;
        role: RoleName;
        phoneNumber: string;
    };
};
export declare class InvoicesController {
    private readonly invoicesService;
    constructor(invoicesService: InvoicesService);
    generateMonthlyRent(req: AuthenticatedRequest, dto: GenerateRentInvoicesDto): Promise<{
        message: string;
        createdCount: number;
        month?: undefined;
        year?: undefined;
        dueDate?: undefined;
    } | {
        message: string;
        month: number;
        year: number;
        dueDate: Date;
        createdCount: number;
    }>;
    createBill(req: AuthenticatedRequest, dto: CreateBillDto): Promise<{
        id: string;
        tenantId: string;
        userId: string;
        amount: import("@prisma/client/runtime/library").Decimal;
        status: import(".prisma/client").$Enums.InvoiceStatus;
        paidAt: Date | null;
        createdAt: Date;
        updatedAt: Date;
        unitId: string;
        billingType: import(".prisma/client").$Enums.BillingType;
        periodMonth: number;
        periodYear: number;
        issueDate: Date;
        dueDate: Date;
        penaltyAmount: import("@prisma/client/runtime/library").Decimal;
        totalAmount: import("@prisma/client/runtime/library").Decimal;
        paidAmount: import("@prisma/client/runtime/library").Decimal;
    }>;
    applyPenalties(req: AuthenticatedRequest, dto: ApplyPenaltiesDto): Promise<{
        message: string;
        penaltyRatePercent: number;
        updatedCount: number;
    }>;
    listPerTenant(req: AuthenticatedRequest, params: TenantIdParamDto): Promise<{
        id: string;
        tenantId: string;
        userId: string;
        amount: import("@prisma/client/runtime/library").Decimal;
        status: import(".prisma/client").$Enums.InvoiceStatus;
        paidAt: Date | null;
        createdAt: Date;
        updatedAt: Date;
        unitId: string;
        billingType: import(".prisma/client").$Enums.BillingType;
        periodMonth: number;
        periodYear: number;
        issueDate: Date;
        dueDate: Date;
        penaltyAmount: import("@prisma/client/runtime/library").Decimal;
        totalAmount: import("@prisma/client/runtime/library").Decimal;
        paidAmount: import("@prisma/client/runtime/library").Decimal;
    }[]>;
}
export {};

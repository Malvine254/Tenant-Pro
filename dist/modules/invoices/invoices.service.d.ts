import { RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { ApplyPenaltiesDto } from './dto/apply-penalties.dto';
import { CreateBillDto } from './dto/create-bill.dto';
import { GenerateRentInvoicesDto } from './dto/generate-rent-invoices.dto';
export declare class InvoicesService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    private toTwoDecimals;
    private assertTenantAccess;
    generateMonthlyRentInvoices(actorUserId: string, actorRole: RoleName, dto: GenerateRentInvoicesDto): Promise<{
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
    runMonthlyRentGenerationCron(): Promise<void>;
    createUtilityBill(actorUserId: string, actorRole: RoleName, dto: CreateBillDto): Promise<{
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
    applyOverduePenalties(actorUserId: string, actorRole: RoleName, dto: ApplyPenaltiesDto): Promise<{
        message: string;
        penaltyRatePercent: number;
        updatedCount: number;
    }>;
    listInvoicesPerTenant(actorUserId: string, actorRole: RoleName, tenantId: string): Promise<{
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

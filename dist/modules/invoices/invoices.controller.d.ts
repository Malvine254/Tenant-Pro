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
    listInvoices(req: AuthenticatedRequest): Promise<({
        tenant: {
            user: {
                id: string;
                createdAt: Date;
                updatedAt: Date;
                phoneNumber: string;
                email: string | null;
                passwordHash: string | null;
                firstName: string | null;
                lastName: string | null;
                profileImageUrl: string | null;
                emergencyContactName: string | null;
                emergencyContactPhone: string | null;
                bio: string | null;
                isActive: boolean;
                roleId: string;
            };
        } & {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            isActive: boolean;
            userId: string;
            unitId: string;
            moveInDate: Date;
            moveOutDate: Date | null;
        };
        unit: {
            property: {
                id: string;
                createdAt: Date;
                updatedAt: Date;
                name: string;
                description: string | null;
                coverImageUrl: string | null;
                addressLine: string;
                city: string;
                state: string | null;
                country: string;
                landlordId: string;
            };
        } & {
            id: string;
            status: import(".prisma/client").$Enums.UnitStatus;
            createdAt: Date;
            updatedAt: Date;
            propertyId: string;
            unitNumber: string;
            floor: string | null;
            rentAmount: import("@prisma/client/runtime/library").Decimal;
            imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
        };
    } & {
        id: string;
        status: import(".prisma/client").$Enums.InvoiceStatus;
        createdAt: Date;
        updatedAt: Date;
        tenantId: string;
        userId: string;
        amount: import("@prisma/client/runtime/library").Decimal;
        paidAt: Date | null;
        unitId: string;
        billingType: import(".prisma/client").$Enums.BillingType;
        periodMonth: number;
        periodYear: number;
        issueDate: Date;
        dueDate: Date;
        penaltyAmount: import("@prisma/client/runtime/library").Decimal;
        totalAmount: import("@prisma/client/runtime/library").Decimal;
        paidAmount: import("@prisma/client/runtime/library").Decimal;
    })[]>;
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
        status: import(".prisma/client").$Enums.InvoiceStatus;
        createdAt: Date;
        updatedAt: Date;
        tenantId: string;
        userId: string;
        amount: import("@prisma/client/runtime/library").Decimal;
        paidAt: Date | null;
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
        status: import(".prisma/client").$Enums.InvoiceStatus;
        createdAt: Date;
        updatedAt: Date;
        tenantId: string;
        userId: string;
        amount: import("@prisma/client/runtime/library").Decimal;
        paidAt: Date | null;
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

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
        unit: {
            property: {
                id: string;
                createdAt: Date;
                updatedAt: Date;
                name: string;
                description: string | null;
                landlordId: string;
                coverImageUrl: string | null;
                addressLine: string;
                city: string;
                state: string | null;
                country: string;
            };
        } & {
            status: import(".prisma/client").$Enums.UnitStatus;
            id: string;
            createdAt: Date;
            updatedAt: Date;
            propertyId: string;
            unitNumber: string;
            floor: string | null;
            rentAmount: import("@prisma/client/runtime/library").Decimal;
            imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
        };
        tenant: {
            user: {
                id: string;
                phoneNumber: string;
                createdAt: Date;
                updatedAt: Date;
                email: string | null;
                firstName: string | null;
                lastName: string | null;
                emergencyContactName: string | null;
                emergencyContactPhone: string | null;
                bio: string | null;
                profileImageUrl: string | null;
                isActive: boolean;
                passwordHash: string | null;
                roleId: string;
            };
        } & {
            id: string;
            userId: string;
            createdAt: Date;
            updatedAt: Date;
            isActive: boolean;
            unitId: string;
            moveInDate: Date;
            moveOutDate: Date | null;
        };
    } & {
        status: import(".prisma/client").$Enums.InvoiceStatus;
        amount: import("@prisma/client/runtime/library").Decimal;
        id: string;
        tenantId: string;
        userId: string;
        paidAt: Date | null;
        createdAt: Date;
        updatedAt: Date;
        unitId: string;
        billingType: import(".prisma/client").$Enums.BillingType;
        dueDate: Date;
        periodMonth: number;
        periodYear: number;
        issueDate: Date;
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
        status: import(".prisma/client").$Enums.InvoiceStatus;
        amount: import("@prisma/client/runtime/library").Decimal;
        id: string;
        tenantId: string;
        userId: string;
        paidAt: Date | null;
        createdAt: Date;
        updatedAt: Date;
        unitId: string;
        billingType: import(".prisma/client").$Enums.BillingType;
        dueDate: Date;
        periodMonth: number;
        periodYear: number;
        issueDate: Date;
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
        status: import(".prisma/client").$Enums.InvoiceStatus;
        amount: import("@prisma/client/runtime/library").Decimal;
        id: string;
        tenantId: string;
        userId: string;
        paidAt: Date | null;
        createdAt: Date;
        updatedAt: Date;
        unitId: string;
        billingType: import(".prisma/client").$Enums.BillingType;
        dueDate: Date;
        periodMonth: number;
        periodYear: number;
        issueDate: Date;
        penaltyAmount: import("@prisma/client/runtime/library").Decimal;
        totalAmount: import("@prisma/client/runtime/library").Decimal;
        paidAmount: import("@prisma/client/runtime/library").Decimal;
    }[]>;
}
export {};

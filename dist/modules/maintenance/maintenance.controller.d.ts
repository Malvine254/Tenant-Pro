import { MaintenanceService } from './maintenance.service';
import { CreateRequestDto } from './dto/create-request.dto';
import { AssignCaretakerDto } from './dto/assign-caretaker.dto';
import { UpdateStatusDto } from './dto/update-status.dto';
import { RequestIdParamDto } from './dto/request-id-param.dto';
export declare class MaintenanceController {
    private readonly maintenanceService;
    constructor(maintenanceService: MaintenanceService);
    createRequest(req: any, dto: CreateRequestDto): Promise<{
        unit: {
            property: {
                id: string;
                name: string;
                landlordId: string;
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
                firstName: string | null;
                lastName: string | null;
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
        reportedBy: {
            id: string;
            firstName: string | null;
            lastName: string | null;
        };
        assignedTo: {
            id: string;
            phoneNumber: string;
            firstName: string | null;
            lastName: string | null;
        } | null;
    } & {
        status: import(".prisma/client").$Enums.MaintenanceStatus;
        id: string;
        tenantId: string;
        createdAt: Date;
        updatedAt: Date;
        description: string;
        unitId: string;
        priority: import(".prisma/client").$Enums.MaintenancePriority;
        title: string;
        reportedById: string;
        assignedToId: string | null;
        resolvedAt: Date | null;
    }>;
    listRequests(req: any): Promise<({
        unit: {
            property: {
                id: string;
                name: string;
                landlordId: string;
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
                firstName: string | null;
                lastName: string | null;
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
        reportedBy: {
            id: string;
            firstName: string | null;
            lastName: string | null;
        };
        assignedTo: {
            id: string;
            phoneNumber: string;
            firstName: string | null;
            lastName: string | null;
        } | null;
    } & {
        status: import(".prisma/client").$Enums.MaintenanceStatus;
        id: string;
        tenantId: string;
        createdAt: Date;
        updatedAt: Date;
        description: string;
        unitId: string;
        priority: import(".prisma/client").$Enums.MaintenancePriority;
        title: string;
        reportedById: string;
        assignedToId: string | null;
        resolvedAt: Date | null;
    })[]>;
    getRequest(req: any, params: RequestIdParamDto): Promise<{
        unit: {
            property: {
                id: string;
                name: string;
                landlordId: string;
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
                firstName: string | null;
                lastName: string | null;
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
        reportedBy: {
            id: string;
            firstName: string | null;
            lastName: string | null;
        };
        assignedTo: {
            id: string;
            phoneNumber: string;
            firstName: string | null;
            lastName: string | null;
        } | null;
    } & {
        status: import(".prisma/client").$Enums.MaintenanceStatus;
        id: string;
        tenantId: string;
        createdAt: Date;
        updatedAt: Date;
        description: string;
        unitId: string;
        priority: import(".prisma/client").$Enums.MaintenancePriority;
        title: string;
        reportedById: string;
        assignedToId: string | null;
        resolvedAt: Date | null;
    }>;
    assignCaretaker(req: any, params: RequestIdParamDto, dto: AssignCaretakerDto): Promise<{
        unit: {
            property: {
                id: string;
                name: string;
                landlordId: string;
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
                firstName: string | null;
                lastName: string | null;
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
        reportedBy: {
            id: string;
            firstName: string | null;
            lastName: string | null;
        };
        assignedTo: {
            id: string;
            phoneNumber: string;
            firstName: string | null;
            lastName: string | null;
        } | null;
    } & {
        status: import(".prisma/client").$Enums.MaintenanceStatus;
        id: string;
        tenantId: string;
        createdAt: Date;
        updatedAt: Date;
        description: string;
        unitId: string;
        priority: import(".prisma/client").$Enums.MaintenancePriority;
        title: string;
        reportedById: string;
        assignedToId: string | null;
        resolvedAt: Date | null;
    }>;
    updateStatus(req: any, params: RequestIdParamDto, dto: UpdateStatusDto): Promise<{
        unit: {
            property: {
                id: string;
                name: string;
                landlordId: string;
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
                firstName: string | null;
                lastName: string | null;
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
        reportedBy: {
            id: string;
            firstName: string | null;
            lastName: string | null;
        };
        assignedTo: {
            id: string;
            phoneNumber: string;
            firstName: string | null;
            lastName: string | null;
        } | null;
    } & {
        status: import(".prisma/client").$Enums.MaintenanceStatus;
        id: string;
        tenantId: string;
        createdAt: Date;
        updatedAt: Date;
        description: string;
        unitId: string;
        priority: import(".prisma/client").$Enums.MaintenancePriority;
        title: string;
        reportedById: string;
        assignedToId: string | null;
        resolvedAt: Date | null;
    }>;
}

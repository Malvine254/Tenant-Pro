import { MaintenanceService } from './maintenance.service';
import { CreateRequestDto } from './dto/create-request.dto';
import { AssignCaretakerDto } from './dto/assign-caretaker.dto';
import { UpdateStatusDto } from './dto/update-status.dto';
import { RequestIdParamDto } from './dto/request-id-param.dto';
export declare class MaintenanceController {
    private readonly maintenanceService;
    constructor(maintenanceService: MaintenanceService);
    createRequest(req: any, dto: CreateRequestDto): Promise<{
        tenant: {
            user: {
                id: string;
                phoneNumber: string;
                firstName: string | null;
                lastName: string | null;
            };
        } & {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            unitId: string;
            userId: string;
            moveInDate: Date;
            moveOutDate: Date | null;
            isActive: boolean;
        };
        unit: {
            property: {
                id: string;
                name: string;
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
        id: string;
        title: string;
        description: string;
        priority: import(".prisma/client").$Enums.MaintenancePriority;
        status: import(".prisma/client").$Enums.MaintenanceStatus;
        resolvedAt: Date | null;
        createdAt: Date;
        updatedAt: Date;
        tenantId: string;
        unitId: string;
        reportedById: string;
        assignedToId: string | null;
    }>;
    listRequests(req: any): Promise<({
        tenant: {
            user: {
                id: string;
                phoneNumber: string;
                firstName: string | null;
                lastName: string | null;
            };
        } & {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            unitId: string;
            userId: string;
            moveInDate: Date;
            moveOutDate: Date | null;
            isActive: boolean;
        };
        unit: {
            property: {
                id: string;
                name: string;
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
        id: string;
        title: string;
        description: string;
        priority: import(".prisma/client").$Enums.MaintenancePriority;
        status: import(".prisma/client").$Enums.MaintenanceStatus;
        resolvedAt: Date | null;
        createdAt: Date;
        updatedAt: Date;
        tenantId: string;
        unitId: string;
        reportedById: string;
        assignedToId: string | null;
    })[]>;
    getRequest(req: any, params: RequestIdParamDto): Promise<{
        tenant: {
            user: {
                id: string;
                phoneNumber: string;
                firstName: string | null;
                lastName: string | null;
            };
        } & {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            unitId: string;
            userId: string;
            moveInDate: Date;
            moveOutDate: Date | null;
            isActive: boolean;
        };
        unit: {
            property: {
                id: string;
                name: string;
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
        id: string;
        title: string;
        description: string;
        priority: import(".prisma/client").$Enums.MaintenancePriority;
        status: import(".prisma/client").$Enums.MaintenanceStatus;
        resolvedAt: Date | null;
        createdAt: Date;
        updatedAt: Date;
        tenantId: string;
        unitId: string;
        reportedById: string;
        assignedToId: string | null;
    }>;
    assignCaretaker(req: any, params: RequestIdParamDto, dto: AssignCaretakerDto): Promise<{
        tenant: {
            user: {
                id: string;
                phoneNumber: string;
                firstName: string | null;
                lastName: string | null;
            };
        } & {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            unitId: string;
            userId: string;
            moveInDate: Date;
            moveOutDate: Date | null;
            isActive: boolean;
        };
        unit: {
            property: {
                id: string;
                name: string;
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
        id: string;
        title: string;
        description: string;
        priority: import(".prisma/client").$Enums.MaintenancePriority;
        status: import(".prisma/client").$Enums.MaintenanceStatus;
        resolvedAt: Date | null;
        createdAt: Date;
        updatedAt: Date;
        tenantId: string;
        unitId: string;
        reportedById: string;
        assignedToId: string | null;
    }>;
    updateStatus(req: any, params: RequestIdParamDto, dto: UpdateStatusDto): Promise<{
        tenant: {
            user: {
                id: string;
                phoneNumber: string;
                firstName: string | null;
                lastName: string | null;
            };
        } & {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            unitId: string;
            userId: string;
            moveInDate: Date;
            moveOutDate: Date | null;
            isActive: boolean;
        };
        unit: {
            property: {
                id: string;
                name: string;
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
        id: string;
        title: string;
        description: string;
        priority: import(".prisma/client").$Enums.MaintenancePriority;
        status: import(".prisma/client").$Enums.MaintenanceStatus;
        resolvedAt: Date | null;
        createdAt: Date;
        updatedAt: Date;
        tenantId: string;
        unitId: string;
        reportedById: string;
        assignedToId: string | null;
    }>;
}

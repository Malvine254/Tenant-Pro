import { RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { AssignCaretakerDto } from './dto/assign-caretaker.dto';
import { CreateRequestDto } from './dto/create-request.dto';
import { UpdateStatusDto } from './dto/update-status.dto';
export declare class MaintenanceService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    createRequest(actorUserId: string, dto: CreateRequestDto): Promise<{
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
    listRequests(actorUserId: string, actorRole: RoleName): Promise<({
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
    assignCaretaker(actorUserId: string, requestId: string, dto: AssignCaretakerDto): Promise<{
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
    updateStatus(actorUserId: string, actorRole: RoleName, requestId: string, dto: UpdateStatusDto): Promise<{
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
    getRequest(actorUserId: string, actorRole: RoleName, requestId: string): Promise<{
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

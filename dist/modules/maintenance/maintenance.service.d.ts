import { RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { NotificationsService } from '../notifications/notifications.service';
import { AssignCaretakerDto } from './dto/assign-caretaker.dto';
import { CreateRequestDto } from './dto/create-request.dto';
import { UpdateStatusDto } from './dto/update-status.dto';
export declare class MaintenanceService {
    private readonly prisma;
    private readonly notificationsService;
    constructor(prisma: PrismaService, notificationsService: NotificationsService);
    createRequest(actorUserId: string, dto: CreateRequestDto): Promise<{
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
    listRequests(actorUserId: string, actorRole: RoleName): Promise<({
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
    assignCaretaker(actorUserId: string, requestId: string, dto: AssignCaretakerDto): Promise<{
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
    updateStatus(actorUserId: string, actorRole: RoleName, requestId: string, dto: UpdateStatusDto): Promise<{
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
    getRequest(actorUserId: string, actorRole: RoleName, requestId: string): Promise<{
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

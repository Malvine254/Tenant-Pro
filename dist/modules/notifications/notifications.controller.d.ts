import { RoleName } from '@prisma/client';
import { NotificationsService } from './notifications.service';
type AuthenticatedRequest = {
    user: {
        userId: string;
        role: RoleName;
        phoneNumber: string;
    };
};
export declare class NotificationsController {
    private readonly notificationsService;
    constructor(notificationsService: NotificationsService);
    list(req: AuthenticatedRequest): Promise<{
        id: string;
        createdAt: Date;
        updatedAt: Date;
        userId: string;
        type: import(".prisma/client").$Enums.NotificationType;
        title: string;
        message: string;
        isRead: boolean;
        readAt: Date | null;
        metadata: import("@prisma/client/runtime/library").JsonValue | null;
    }[]>;
    markRead(req: AuthenticatedRequest, id: string): Promise<{
        id: string;
        createdAt: Date;
        updatedAt: Date;
        userId: string;
        type: import(".prisma/client").$Enums.NotificationType;
        title: string;
        message: string;
        isRead: boolean;
        readAt: Date | null;
        metadata: import("@prisma/client/runtime/library").JsonValue | null;
    }>;
    markAllRead(req: AuthenticatedRequest): Promise<{
        message: string;
        updatedCount: number;
    }>;
}
export {};

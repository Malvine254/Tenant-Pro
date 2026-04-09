import { NotificationType, Prisma } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
export declare class NotificationsService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    createNotification(userId: string, type: NotificationType, title: string, message: string, metadata?: Prisma.InputJsonValue): Promise<{
        id: string;
        createdAt: Date;
        updatedAt: Date;
        userId: string;
        type: import(".prisma/client").$Enums.NotificationType;
        title: string;
        message: string;
        isRead: boolean;
        readAt: Date | null;
        metadata: Prisma.JsonValue | null;
    }>;
    private seedUserFeed;
    listForUser(userId: string): Promise<{
        id: string;
        createdAt: Date;
        updatedAt: Date;
        userId: string;
        type: import(".prisma/client").$Enums.NotificationType;
        title: string;
        message: string;
        isRead: boolean;
        readAt: Date | null;
        metadata: Prisma.JsonValue | null;
    }[]>;
    markRead(userId: string, notificationId: string): Promise<{
        id: string;
        createdAt: Date;
        updatedAt: Date;
        userId: string;
        type: import(".prisma/client").$Enums.NotificationType;
        title: string;
        message: string;
        isRead: boolean;
        readAt: Date | null;
        metadata: Prisma.JsonValue | null;
    }>;
    markAllRead(userId: string): Promise<{
        message: string;
        updatedCount: number;
    }>;
}

import { NotificationType, Prisma } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
export declare class NotificationsService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    createNotification(userId: string, type: NotificationType, title: string, message: string, metadata?: Prisma.InputJsonValue): Promise<{
        id: string;
        type: import(".prisma/client").$Enums.NotificationType;
        title: string;
        message: string;
        isRead: boolean;
        readAt: Date | null;
        metadata: Prisma.JsonValue | null;
        createdAt: Date;
        updatedAt: Date;
        userId: string;
    }>;
    private seedUserFeed;
    listForUser(userId: string): Promise<{
        id: string;
        type: import(".prisma/client").$Enums.NotificationType;
        title: string;
        message: string;
        isRead: boolean;
        readAt: Date | null;
        metadata: Prisma.JsonValue | null;
        createdAt: Date;
        updatedAt: Date;
        userId: string;
    }[]>;
    markRead(userId: string, notificationId: string): Promise<{
        id: string;
        type: import(".prisma/client").$Enums.NotificationType;
        title: string;
        message: string;
        isRead: boolean;
        readAt: Date | null;
        metadata: Prisma.JsonValue | null;
        createdAt: Date;
        updatedAt: Date;
        userId: string;
    }>;
    markAllRead(userId: string): Promise<{
        message: string;
        updatedCount: number;
    }>;
}

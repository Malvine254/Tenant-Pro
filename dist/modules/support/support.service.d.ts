import { PrismaService } from '../../prisma/prisma.service';
import { NotificationsService } from '../notifications/notifications.service';
import { SendSupportMessageDto } from './dto/send-support-message.dto';
export declare class SupportService {
    private readonly prisma;
    private readonly notificationsService;
    constructor(prisma: PrismaService, notificationsService: NotificationsService);
    private resolveBody;
    private serializeMessage;
    private serializeConversation;
    private getOrCreateConversation;
    private touchConversation;
    private notifyAdmins;
    listMessages(userId: string): Promise<{
        id: string;
        senderId: string;
        topic: string;
        message: string;
        attachmentUri: string | null;
        attachmentName: string | null;
        isFromTenant: boolean;
        status: string;
        timestamp: number;
    }[]>;
    sendMessage(userId: string, dto: SendSupportMessageDto): Promise<{
        id: string;
        senderId: string;
        topic: string;
        message: string;
        attachmentUri: string | null;
        attachmentName: string | null;
        isFromTenant: boolean;
        status: string;
        timestamp: number;
    }[]>;
    listConversationsForOps(): Promise<{
        id: string;
        tenantUserId: string;
        tenantName: string;
        phoneNumber: string;
        email: string | null;
        isTenantActive: boolean;
        topic: string;
        subject: string;
        isOpen: boolean;
        lastMessage: string;
        lastMessageAt: string;
        lastSender: string | null;
    }[]>;
    getConversationMessages(conversationId: string): Promise<{
        conversation: {
            id: string;
            tenantUserId: string;
            tenantName: string;
            phoneNumber: string;
            email: string | null;
            isTenantActive: boolean;
            topic: string;
            subject: string;
            isOpen: boolean;
            lastMessage: string;
            lastMessageAt: string;
            lastSender: string | null;
        };
        messages: {
            id: string;
            senderId: string;
            topic: string;
            message: string;
            attachmentUri: string | null;
            attachmentName: string | null;
            isFromTenant: boolean;
            status: string;
            timestamp: number;
        }[];
    }>;
    startConversationForOps(staffUserId: string, dto: SendSupportMessageDto): Promise<{
        conversation: {
            id: string;
            tenantUserId: string;
            tenantName: string;
            phoneNumber: string;
            email: string | null;
            isTenantActive: boolean;
            topic: string;
            subject: string;
            isOpen: boolean;
            lastMessage: string;
            lastMessageAt: string;
            lastSender: string | null;
        };
        messages: {
            id: string;
            senderId: string;
            topic: string;
            message: string;
            attachmentUri: string | null;
            attachmentName: string | null;
            isFromTenant: boolean;
            status: string;
            timestamp: number;
        }[];
    }>;
    replyToConversation(staffUserId: string, conversationId: string, dto: SendSupportMessageDto): Promise<{
        conversation: {
            id: string;
            tenantUserId: string;
            tenantName: string;
            phoneNumber: string;
            email: string | null;
            isTenantActive: boolean;
            topic: string;
            subject: string;
            isOpen: boolean;
            lastMessage: string;
            lastMessageAt: string;
            lastSender: string | null;
        };
        messages: {
            id: string;
            senderId: string;
            topic: string;
            message: string;
            attachmentUri: string | null;
            attachmentName: string | null;
            isFromTenant: boolean;
            status: string;
            timestamp: number;
        }[];
    }>;
}

import { RoleName } from '@prisma/client';
import { SendSupportMessageDto } from './dto/send-support-message.dto';
import { SupportService } from './support.service';
type AuthenticatedRequest = {
    user: {
        userId: string;
        role: RoleName;
        phoneNumber: string;
    };
};
export declare class SupportController {
    private readonly supportService;
    constructor(supportService: SupportService);
    uploadAttachment(file?: {
        originalname: string;
        filename: string;
        size: number;
        mimetype: string;
    }): {
        fileName: string;
        attachmentName: string;
        attachmentUri: string;
        size: number;
        mimeType: string;
    };
    listMessages(req: AuthenticatedRequest): Promise<{
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
    sendMessage(req: AuthenticatedRequest, dto: SendSupportMessageDto): Promise<{
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
    listConversations(): Promise<{
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
    startConversation(req: AuthenticatedRequest, dto: SendSupportMessageDto): Promise<{
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
    replyToConversation(req: AuthenticatedRequest, conversationId: string, dto: SendSupportMessageDto): Promise<{
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
export {};

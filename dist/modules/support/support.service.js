"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.SupportService = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const prisma_service_1 = require("../../prisma/prisma.service");
const notifications_service_1 = require("../notifications/notifications.service");
let SupportService = class SupportService {
    constructor(prisma, notificationsService) {
        this.prisma = prisma;
        this.notificationsService = notificationsService;
    }
    resolveBody(dto) {
        const text = dto.text?.trim();
        const attachmentName = dto.attachmentName?.trim();
        if (!text && !attachmentName) {
            throw new common_1.BadRequestException('Message text or an attachment is required');
        }
        return text || `Shared attachment: ${attachmentName}`;
    }
    serializeMessage(message) {
        return {
            id: message.id,
            senderId: message.senderId,
            topic: message.topic ?? 'General',
            message: message.body,
            attachmentUri: message.attachmentUri,
            attachmentName: message.attachmentName,
            isFromTenant: message.isFromTenant,
            status: message.status,
            timestamp: message.createdAt.getTime(),
        };
    }
    serializeConversation(conversation) {
        const latest = conversation.messages[0];
        const tenantName = [conversation.tenantUser.firstName, conversation.tenantUser.lastName]
            .filter(Boolean)
            .join(' ')
            .trim();
        return {
            id: conversation.id,
            tenantUserId: conversation.tenantUserId,
            tenantName: tenantName || conversation.tenantUser.phoneNumber,
            phoneNumber: conversation.tenantUser.phoneNumber,
            email: conversation.tenantUser.email,
            isTenantActive: conversation.tenantUser.isActive,
            topic: conversation.topic ?? 'General',
            subject: conversation.subject ?? `${conversation.topic ?? 'General'} support`,
            isOpen: conversation.isOpen,
            lastMessage: latest?.body ?? 'No messages yet',
            lastMessageAt: (latest?.createdAt ?? conversation.updatedAt).toISOString(),
            lastSender: latest ? (latest.isFromTenant ? 'tenant' : 'staff') : null,
        };
    }
    async getOrCreateConversation(tenantUserId, topic = 'General', subject) {
        const existing = await this.prisma.supportConversation.findFirst({
            where: {
                tenantUserId,
                topic,
                isOpen: true,
            },
            orderBy: { updatedAt: 'desc' },
        });
        if (existing)
            return existing;
        return this.prisma.supportConversation.create({
            data: {
                tenantUserId,
                topic,
                subject: subject?.trim() || `${topic} support`,
            },
        });
    }
    async touchConversation(conversationId, topic, subject) {
        await this.prisma.supportConversation.update({
            where: { id: conversationId },
            data: {
                topic,
                ...(subject ? { subject } : {}),
            },
        });
    }
    async notifyAdmins(conversationId, tenantUserId, topic) {
        const admins = await this.prisma.user.findMany({
            where: {
                isActive: true,
                role: { is: { name: client_1.RoleName.ADMIN } },
            },
            select: { id: true },
        });
        for (const admin of admins) {
            await this.notificationsService.createNotification(admin.id, client_1.NotificationType.MESSAGE, 'New tenant message', `A tenant sent a new ${topic} message in support chat.`, { conversationId, tenantUserId, topic });
        }
    }
    async listMessages(userId) {
        const messages = await this.prisma.supportMessage.findMany({
            where: { conversation: { tenantUserId: userId } },
            orderBy: { createdAt: 'asc' },
        });
        return messages.map((message) => this.serializeMessage(message));
    }
    async sendMessage(userId, dto) {
        const topic = dto.topic?.trim() || 'General';
        const body = this.resolveBody(dto);
        const conversation = await this.getOrCreateConversation(userId, topic, dto.subject);
        await this.prisma.supportMessage.create({
            data: {
                conversationId: conversation.id,
                senderId: userId,
                topic,
                body,
                attachmentUri: dto.attachmentUri,
                attachmentName: dto.attachmentName,
                isFromTenant: true,
                status: 'Sent',
            },
        });
        await this.touchConversation(conversation.id, topic, dto.subject?.trim() || conversation.subject || undefined);
        await this.notifyAdmins(conversation.id, userId, topic);
        await this.notificationsService.createNotification(userId, client_1.NotificationType.MESSAGE, 'Message sent', `Your ${topic} message has been sent to the management team.`, { conversationId: conversation.id, topic });
        return this.listMessages(userId);
    }
    async listConversationsForOps() {
        const conversations = await this.prisma.supportConversation.findMany({
            include: {
                tenantUser: {
                    select: {
                        id: true,
                        firstName: true,
                        lastName: true,
                        phoneNumber: true,
                        email: true,
                        isActive: true,
                    },
                },
                messages: {
                    select: {
                        body: true,
                        isFromTenant: true,
                        createdAt: true,
                    },
                    orderBy: { createdAt: 'desc' },
                    take: 1,
                },
            },
            orderBy: { updatedAt: 'desc' },
        });
        return conversations.map((conversation) => this.serializeConversation(conversation));
    }
    async getConversationMessages(conversationId) {
        const conversation = await this.prisma.supportConversation.findUnique({
            where: { id: conversationId },
            include: {
                tenantUser: {
                    select: {
                        id: true,
                        firstName: true,
                        lastName: true,
                        phoneNumber: true,
                        email: true,
                        isActive: true,
                    },
                },
                messages: {
                    orderBy: { createdAt: 'asc' },
                },
            },
        });
        if (!conversation) {
            throw new common_1.NotFoundException('Conversation not found');
        }
        return {
            conversation: this.serializeConversation({
                ...conversation,
                messages: conversation.messages.slice(-1).map((message) => ({
                    body: message.body,
                    isFromTenant: message.isFromTenant,
                    createdAt: message.createdAt,
                })),
            }),
            messages: conversation.messages.map((message) => this.serializeMessage(message)),
        };
    }
    async startConversationForOps(staffUserId, dto) {
        if (!dto.tenantUserId?.trim()) {
            throw new common_1.BadRequestException('tenantUserId is required to start a conversation');
        }
        const tenant = await this.prisma.user.findFirst({
            where: {
                id: dto.tenantUserId.trim(),
                role: { is: { name: client_1.RoleName.TENANT } },
            },
            select: { id: true },
        });
        if (!tenant) {
            throw new common_1.NotFoundException('Tenant not found');
        }
        const topic = dto.topic?.trim() || 'General';
        const body = this.resolveBody(dto);
        const conversation = await this.getOrCreateConversation(tenant.id, topic, dto.subject);
        await this.prisma.supportMessage.create({
            data: {
                conversationId: conversation.id,
                senderId: staffUserId,
                topic,
                body,
                attachmentUri: dto.attachmentUri,
                attachmentName: dto.attachmentName,
                isFromTenant: false,
                status: 'Sent',
            },
        });
        await this.touchConversation(conversation.id, topic, dto.subject?.trim() || conversation.subject || undefined);
        await this.notificationsService.createNotification(tenant.id, client_1.NotificationType.MESSAGE, 'New message from management', `You have a new ${topic} message from the management team.`, { conversationId: conversation.id, topic });
        return this.getConversationMessages(conversation.id);
    }
    async replyToConversation(staffUserId, conversationId, dto) {
        const existing = await this.prisma.supportConversation.findUnique({
            where: { id: conversationId },
            select: {
                id: true,
                tenantUserId: true,
                topic: true,
                subject: true,
            },
        });
        if (!existing) {
            throw new common_1.NotFoundException('Conversation not found');
        }
        const topic = dto.topic?.trim() || existing.topic || 'General';
        const body = this.resolveBody(dto);
        await this.prisma.supportMessage.create({
            data: {
                conversationId: existing.id,
                senderId: staffUserId,
                topic,
                body,
                attachmentUri: dto.attachmentUri,
                attachmentName: dto.attachmentName,
                isFromTenant: false,
                status: 'Sent',
            },
        });
        await this.touchConversation(existing.id, topic, dto.subject?.trim() || existing.subject || undefined);
        await this.notificationsService.createNotification(existing.tenantUserId, client_1.NotificationType.MESSAGE, 'New message from management', `You have a new ${topic} message from the management team.`, { conversationId: existing.id, topic });
        return this.getConversationMessages(existing.id);
    }
};
exports.SupportService = SupportService;
exports.SupportService = SupportService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService,
        notifications_service_1.NotificationsService])
], SupportService);
//# sourceMappingURL=support.service.js.map
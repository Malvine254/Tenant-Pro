import { BadRequestException, Injectable, NotFoundException } from '@nestjs/common';
import { NotificationType, RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { NotificationsService } from '../notifications/notifications.service';
import { SendSupportMessageDto } from './dto/send-support-message.dto';

@Injectable()
export class SupportService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly notificationsService: NotificationsService,
  ) {}

  private resolveBody(dto: SendSupportMessageDto) {
    const text = dto.text?.trim();
    const attachmentName = dto.attachmentName?.trim();

    if (!text && !attachmentName) {
      throw new BadRequestException('Message text or an attachment is required');
    }

    return text || `Shared attachment: ${attachmentName}`;
  }

  private serializeMessage(message: {
    id: string;
    senderId: string;
    topic: string | null;
    body: string;
    attachmentUri: string | null;
    attachmentName: string | null;
    isFromTenant: boolean;
    status: string;
    createdAt: Date;
  }) {
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

  private serializeConversation(conversation: {
    id: string;
    tenantUserId: string;
    topic: string | null;
    subject: string | null;
    isOpen: boolean;
    updatedAt: Date;
    tenantUser: {
      id: string;
      firstName: string | null;
      lastName: string | null;
      phoneNumber: string;
      email: string | null;
      isActive: boolean;
    };
    messages: Array<{
      body: string;
      isFromTenant: boolean;
      createdAt: Date;
    }>;
  }) {
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

  private async getOrCreateConversation(tenantUserId: string, topic = 'General', subject?: string) {
    const existing = await this.prisma.supportConversation.findFirst({
      where: {
        tenantUserId,
        topic,
        isOpen: true,
      },
      orderBy: { updatedAt: 'desc' },
    });

    if (existing) return existing;

    return this.prisma.supportConversation.create({
      data: {
        tenantUserId,
        topic,
        subject: subject?.trim() || `${topic} support`,
      },
    });
  }

  private async touchConversation(conversationId: string, topic: string, subject?: string) {
    await this.prisma.supportConversation.update({
      where: { id: conversationId },
      data: {
        topic,
        ...(subject ? { subject } : {}),
      },
    });
  }

  private async notifyAdmins(conversationId: string, tenantUserId: string, topic: string) {
    const admins = await this.prisma.user.findMany({
      where: {
        isActive: true,
        role: { is: { name: RoleName.ADMIN } },
      },
      select: { id: true },
    });

    for (const admin of admins) {
      await this.notificationsService.createNotification(
        admin.id,
        NotificationType.MESSAGE,
        'New tenant message',
        `A tenant sent a new ${topic} message in support chat.`,
        { conversationId, tenantUserId, topic },
      );
    }
  }

  async listMessages(userId: string) {
    const messages = await this.prisma.supportMessage.findMany({
      where: { conversation: { tenantUserId: userId } },
      orderBy: { createdAt: 'asc' },
    });

    return messages.map((message) => this.serializeMessage(message));
  }

  async sendMessage(userId: string, dto: SendSupportMessageDto) {
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

    await this.notificationsService.createNotification(
      userId,
      NotificationType.MESSAGE,
      'Message sent',
      `Your ${topic} message has been sent to the management team.`,
      { conversationId: conversation.id, topic },
    );

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

  async getConversationMessages(conversationId: string) {
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
      throw new NotFoundException('Conversation not found');
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

  async startConversationForOps(staffUserId: string, dto: SendSupportMessageDto) {
    if (!dto.tenantUserId?.trim()) {
      throw new BadRequestException('tenantUserId is required to start a conversation');
    }

    const tenant = await this.prisma.user.findFirst({
      where: {
        id: dto.tenantUserId.trim(),
        role: { is: { name: RoleName.TENANT } },
      },
      select: { id: true },
    });

    if (!tenant) {
      throw new NotFoundException('Tenant not found');
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

    await this.notificationsService.createNotification(
      tenant.id,
      NotificationType.MESSAGE,
      'New message from management',
      `You have a new ${topic} message from the management team.`,
      { conversationId: conversation.id, topic },
    );

    return this.getConversationMessages(conversation.id);
  }

  async replyToConversation(staffUserId: string, conversationId: string, dto: SendSupportMessageDto) {
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
      throw new NotFoundException('Conversation not found');
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

    await this.notificationsService.createNotification(
      existing.tenantUserId,
      NotificationType.MESSAGE,
      'New message from management',
      `You have a new ${topic} message from the management team.`,
      { conversationId: existing.id, topic },
    );

    return this.getConversationMessages(existing.id);
  }
}

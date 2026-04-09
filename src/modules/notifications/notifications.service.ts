import { Injectable, NotFoundException } from '@nestjs/common';
import { NotificationType, Prisma } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';

@Injectable()
export class NotificationsService {
  constructor(private readonly prisma: PrismaService) {}

  async createNotification(
    userId: string,
    type: NotificationType,
    title: string,
    message: string,
    metadata?: Prisma.InputJsonValue,
  ) {
    return this.prisma.notification.create({
      data: {
        userId,
        type,
        title,
        message,
        metadata: metadata ?? undefined,
      },
    });
  }

  private async seedUserFeed(userId: string) {
    const existing = await this.prisma.notification.count({ where: { userId } });
    if (existing > 0) return;

    const [invoices, payments, maintenance] = await Promise.all([
      this.prisma.invoice.findMany({
        where: { userId },
        include: { unit: { include: { property: true } } },
        orderBy: { dueDate: 'desc' },
        take: 4,
      }),
      this.prisma.payment.findMany({
        where: { userId },
        include: { invoice: true },
        orderBy: { createdAt: 'desc' },
        take: 3,
      }),
      this.prisma.maintenanceRequest.findMany({
        where: { reportedById: userId },
        include: { unit: { include: { property: true } } },
        orderBy: { createdAt: 'desc' },
        take: 3,
      }),
    ]);

    const toCreate: Array<{
      userId: string;
      type: NotificationType;
      title: string;
      message: string;
      metadata?: Prisma.InputJsonValue;
      createdAt?: Date;
    }> = [];

    invoices.forEach((invoice) => {
      toCreate.push({
        userId,
        type: NotificationType.INVOICE,
        title: `${invoice.billingType} invoice ${invoice.status.toLowerCase()}`,
        message: `Invoice for ${invoice.unit.property.name} ${invoice.unit.unitNumber} is ${invoice.status.toLowerCase()} and due on ${invoice.dueDate.toDateString()}.`,
        metadata: { invoiceId: invoice.id, status: invoice.status },
        createdAt: invoice.updatedAt,
      });
    });

    payments.forEach((payment) => {
      toCreate.push({
        userId,
        type: NotificationType.PAYMENT,
        title: `Payment ${payment.status.toLowerCase()}`,
        message: `Payment of KES ${Number(payment.amount).toFixed(2)} for ${payment.invoice.billingType} is ${payment.status.toLowerCase()}.`,
        metadata: { paymentId: payment.id, invoiceId: payment.invoiceId },
        createdAt: payment.updatedAt,
      });
    });

    maintenance.forEach((request) => {
      toCreate.push({
        userId,
        type: NotificationType.MAINTENANCE,
        title: `Maintenance ${request.status.toLowerCase()}`,
        message: `${request.title} at ${request.unit.property.name} is currently ${request.status.replace('_', ' ').toLowerCase()}.`,
        metadata: { requestId: request.id, status: request.status },
        createdAt: request.updatedAt,
      });
    });

    if (toCreate.length === 0) {
      toCreate.push({
        userId,
        type: NotificationType.GENERAL,
        title: 'Welcome to Tenant Pro',
        message: 'Your account is ready. New invoices, payments, and maintenance updates will appear here.',
      });
    }

    for (const item of toCreate) {
      await this.prisma.notification.create({
        data: {
          userId: item.userId,
          type: item.type,
          title: item.title,
          message: item.message,
          metadata: item.metadata,
          createdAt: item.createdAt,
        },
      });
    }
  }

  async listForUser(userId: string) {
    await this.seedUserFeed(userId);
    return this.prisma.notification.findMany({
      where: { userId },
      orderBy: [{ isRead: 'asc' }, { createdAt: 'desc' }],
    });
  }

  async markRead(userId: string, notificationId: string) {
    const notification = await this.prisma.notification.findFirst({
      where: { id: notificationId, userId },
    });

    if (!notification) {
      throw new NotFoundException('Notification not found');
    }

    return this.prisma.notification.update({
      where: { id: notificationId },
      data: { isRead: true, readAt: new Date() },
    });
  }

  async markAllRead(userId: string) {
    const result = await this.prisma.notification.updateMany({
      where: { userId, isRead: false },
      data: { isRead: true, readAt: new Date() },
    });

    return {
      message: 'Notifications marked as read',
      updatedCount: result.count,
    };
  }
}

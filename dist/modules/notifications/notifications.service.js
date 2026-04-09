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
exports.NotificationsService = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const prisma_service_1 = require("../../prisma/prisma.service");
let NotificationsService = class NotificationsService {
    constructor(prisma) {
        this.prisma = prisma;
    }
    async createNotification(userId, type, title, message, metadata) {
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
    async seedUserFeed(userId) {
        const existing = await this.prisma.notification.count({ where: { userId } });
        if (existing > 0)
            return;
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
        const toCreate = [];
        invoices.forEach((invoice) => {
            toCreate.push({
                userId,
                type: client_1.NotificationType.INVOICE,
                title: `${invoice.billingType} invoice ${invoice.status.toLowerCase()}`,
                message: `Invoice for ${invoice.unit.property.name} ${invoice.unit.unitNumber} is ${invoice.status.toLowerCase()} and due on ${invoice.dueDate.toDateString()}.`,
                metadata: { invoiceId: invoice.id, status: invoice.status },
                createdAt: invoice.updatedAt,
            });
        });
        payments.forEach((payment) => {
            toCreate.push({
                userId,
                type: client_1.NotificationType.PAYMENT,
                title: `Payment ${payment.status.toLowerCase()}`,
                message: `Payment of KES ${Number(payment.amount).toFixed(2)} for ${payment.invoice.billingType} is ${payment.status.toLowerCase()}.`,
                metadata: { paymentId: payment.id, invoiceId: payment.invoiceId },
                createdAt: payment.updatedAt,
            });
        });
        maintenance.forEach((request) => {
            toCreate.push({
                userId,
                type: client_1.NotificationType.MAINTENANCE,
                title: `Maintenance ${request.status.toLowerCase()}`,
                message: `${request.title} at ${request.unit.property.name} is currently ${request.status.replace('_', ' ').toLowerCase()}.`,
                metadata: { requestId: request.id, status: request.status },
                createdAt: request.updatedAt,
            });
        });
        if (toCreate.length === 0) {
            toCreate.push({
                userId,
                type: client_1.NotificationType.GENERAL,
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
    async listForUser(userId) {
        await this.seedUserFeed(userId);
        return this.prisma.notification.findMany({
            where: { userId },
            orderBy: [{ isRead: 'asc' }, { createdAt: 'desc' }],
        });
    }
    async markRead(userId, notificationId) {
        const notification = await this.prisma.notification.findFirst({
            where: { id: notificationId, userId },
        });
        if (!notification) {
            throw new common_1.NotFoundException('Notification not found');
        }
        return this.prisma.notification.update({
            where: { id: notificationId },
            data: { isRead: true, readAt: new Date() },
        });
    }
    async markAllRead(userId) {
        const result = await this.prisma.notification.updateMany({
            where: { userId, isRead: false },
            data: { isRead: true, readAt: new Date() },
        });
        return {
            message: 'Notifications marked as read',
            updatedCount: result.count,
        };
    }
};
exports.NotificationsService = NotificationsService;
exports.NotificationsService = NotificationsService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService])
], NotificationsService);
//# sourceMappingURL=notifications.service.js.map
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
var RemindersService_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.RemindersService = void 0;
const common_1 = require("@nestjs/common");
const schedule_1 = require("@nestjs/schedule");
const client_1 = require("@prisma/client");
const prisma_service_1 = require("../../prisma/prisma.service");
const email_service_1 = require("../email/email.service");
let RemindersService = RemindersService_1 = class RemindersService {
    constructor(prisma, emailService) {
        this.prisma = prisma;
        this.emailService = emailService;
        this.logger = new common_1.Logger(RemindersService_1.name);
    }
    async sendRentReminders() {
        this.logger.log('Starting rent reminder job...');
        const threeDaysFromNow = new Date();
        threeDaysFromNow.setDate(threeDaysFromNow.getDate() + 3);
        threeDaysFromNow.setHours(0, 0, 0, 0);
        const threeDaysFromNowEnd = new Date(threeDaysFromNow);
        threeDaysFromNowEnd.setHours(23, 59, 59, 999);
        try {
            const upcomingInvoices = await this.prisma.invoice.findMany({
                where: {
                    status: client_1.InvoiceStatus.PENDING,
                    dueDate: {
                        gte: threeDaysFromNow,
                        lte: threeDaysFromNowEnd,
                    },
                },
                include: {
                    tenant: {
                        include: {
                            user: true,
                        },
                    },
                    unit: {
                        include: {
                            property: true,
                        },
                    },
                },
            });
            this.logger.log(`Found ${upcomingInvoices.length} invoices due in 3 days`);
            for (const invoice of upcomingInvoices) {
                if (invoice.tenant?.user?.email) {
                    await this.emailService.sendRentReminderEmail(invoice.tenant.user.email, invoice.tenant.user.firstName || 'Tenant', invoice.amount.toNumber(), invoice.dueDate, invoice.unit.property.name);
                }
            }
            this.logger.log('Rent reminders sent successfully');
        }
        catch (error) {
            this.logger.error('Error sending rent reminders:', error);
        }
    }
    async sendOverdueNotices() {
        this.logger.log('Starting overdue notices job...');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        try {
            const overdueInvoices = await this.prisma.invoice.findMany({
                where: {
                    status: client_1.InvoiceStatus.PENDING,
                    dueDate: {
                        lt: today,
                    },
                },
                include: {
                    tenant: {
                        include: {
                            user: true,
                        },
                    },
                    unit: {
                        include: {
                            property: true,
                        },
                    },
                },
            });
            this.logger.log(`Found ${overdueInvoices.length} overdue invoices`);
            for (const invoice of overdueInvoices) {
                if (invoice.tenant?.user?.email) {
                    const daysOverdue = Math.floor((today.getTime() - invoice.dueDate.getTime()) / (1000 * 60 * 60 * 24));
                    await this.emailService.sendOverdueNoticeEmail(invoice.tenant.user.email, invoice.tenant.user.firstName || 'Tenant', invoice.amount.toNumber(), daysOverdue, invoice.unit.property.name);
                }
            }
            this.logger.log('Overdue notices sent successfully');
        }
        catch (error) {
            this.logger.error('Error sending overdue notices:', error);
        }
    }
    async sendManualRentReminder(invoiceId) {
        const invoice = await this.prisma.invoice.findUnique({
            where: { id: invoiceId },
            include: {
                tenant: {
                    include: {
                        user: true,
                    },
                },
                unit: {
                    include: {
                        property: true,
                    },
                },
            },
        });
        if (!invoice) {
            throw new Error('Invoice not found');
        }
        if (!invoice.tenant?.user?.email) {
            throw new Error('Tenant email not found');
        }
        await this.emailService.sendRentReminderEmail(invoice.tenant.user.email, invoice.tenant.user.firstName || 'Tenant', invoice.amount.toNumber(), invoice.dueDate, invoice.unit.property.name);
        return { message: 'Reminder sent successfully' };
    }
    async sendMaintenanceUpdate(maintenanceRequestId, status, updateMessage) {
        const request = await this.prisma.maintenanceRequest.findUnique({
            where: { id: maintenanceRequestId },
            include: {
                tenant: {
                    include: {
                        user: true,
                    },
                },
            },
        });
        if (!request) {
            throw new Error('Maintenance request not found');
        }
        if (!request.tenant?.user?.email) {
            throw new Error('Tenant email not found');
        }
        await this.emailService.sendMaintenanceUpdateEmail(request.tenant.user.email, request.tenant.user.firstName || 'Tenant', request.id, status, updateMessage);
        return { message: 'Maintenance update sent successfully' };
    }
    async sendWelcomeEmail(userId) {
        const user = await this.prisma.user.findUnique({
            where: { id: userId },
        });
        if (!user || !user.email) {
            throw new Error('User or email not found');
        }
        await this.emailService.sendWelcomeEmail(user.email, user.firstName || 'User');
        return { message: 'Welcome email sent successfully' };
    }
};
exports.RemindersService = RemindersService;
__decorate([
    (0, schedule_1.Cron)(schedule_1.CronExpression.EVERY_DAY_AT_9AM),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", []),
    __metadata("design:returntype", Promise)
], RemindersService.prototype, "sendRentReminders", null);
__decorate([
    (0, schedule_1.Cron)(schedule_1.CronExpression.EVERY_DAY_AT_10AM),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", []),
    __metadata("design:returntype", Promise)
], RemindersService.prototype, "sendOverdueNotices", null);
exports.RemindersService = RemindersService = RemindersService_1 = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService,
        email_service_1.EmailService])
], RemindersService);
//# sourceMappingURL=reminders.service.js.map
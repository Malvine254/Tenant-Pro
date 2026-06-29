import { Injectable, Logger } from '@nestjs/common';
import { Cron, CronExpression } from '@nestjs/schedule';
import { InvoiceStatus } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { EmailService } from '../email/email.service';

@Injectable()
export class RemindersService {
  private readonly logger = new Logger(RemindersService.name);

  constructor(
    private readonly prisma: PrismaService,
    private readonly emailService: EmailService,
  ) {}

  /**
   * Send rent reminders 3 days before due date
   * Runs daily at 9 AM
   */
  @Cron(CronExpression.EVERY_DAY_AT_9AM)
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
          status: InvoiceStatus.PENDING,
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
          await this.emailService.sendRentReminderEmail(
            invoice.tenant.user.email,
            invoice.tenant.user.firstName || 'Tenant',
            invoice.amount.toNumber(),
            invoice.dueDate,
            invoice.unit.property.name,
          );
        }
      }

      this.logger.log('Rent reminders sent successfully');
    } catch (error) {
      this.logger.error('Error sending rent reminders:', error);
    }
  }

  /**
   * Send overdue payment notices
   * Runs daily at 10 AM
   */
  @Cron(CronExpression.EVERY_DAY_AT_10AM)
  async sendOverdueNotices() {
    this.logger.log('Starting overdue notices job...');

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    try {
      const overdueInvoices = await this.prisma.invoice.findMany({
        where: {
          status: InvoiceStatus.PENDING,
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
          const daysOverdue = Math.floor(
            (today.getTime() - invoice.dueDate.getTime()) / (1000 * 60 * 60 * 24),
          );

          await this.emailService.sendOverdueNoticeEmail(
            invoice.tenant.user.email,
            invoice.tenant.user.firstName || 'Tenant',
            invoice.amount.toNumber(),
            daysOverdue,
            invoice.unit.property.name,
          );
        }
      }

      this.logger.log('Overdue notices sent successfully');
    } catch (error) {
      this.logger.error('Error sending overdue notices:', error);
    }
  }

  /**
   * Manually trigger rent reminder for a specific invoice
   */
  async sendManualRentReminder(invoiceId: string) {
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

    await this.emailService.sendRentReminderEmail(
      invoice.tenant.user.email,
      invoice.tenant.user.firstName || 'Tenant',
      invoice.amount.toNumber(),
      invoice.dueDate,
      invoice.unit.property.name,
    );

    return { message: 'Reminder sent successfully' };
  }

  /**
   * Send maintenance update notification
   */
  async sendMaintenanceUpdate(
    maintenanceRequestId: string,
    status: string,
    updateMessage: string,
  ) {
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

    await this.emailService.sendMaintenanceUpdateEmail(
      request.tenant.user.email,
      request.tenant.user.firstName || 'Tenant',
      request.id,
      status,
      updateMessage,
    );

    return { message: 'Maintenance update sent successfully' };
  }

  /**
   * Send welcome email to new user
   */
  async sendWelcomeEmail(userId: string) {
    const user = await this.prisma.user.findUnique({
      where: { id: userId },
    });

    if (!user || !user.email) {
      throw new Error('User or email not found');
    }

    await this.emailService.sendWelcomeEmail(user.email, user.firstName || 'User');

    return { message: 'Welcome email sent successfully' };
  }
}

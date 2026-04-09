import { PrismaService } from '../../prisma/prisma.service';
import { EmailService } from '../email/email.service';
export declare class RemindersService {
    private readonly prisma;
    private readonly emailService;
    private readonly logger;
    constructor(prisma: PrismaService, emailService: EmailService);
    sendRentReminders(): Promise<void>;
    sendOverdueNotices(): Promise<void>;
    sendManualRentReminder(invoiceId: string): Promise<{
        message: string;
    }>;
    sendMaintenanceUpdate(maintenanceRequestId: string, status: string, updateMessage: string): Promise<{
        message: string;
    }>;
    sendWelcomeEmail(userId: string): Promise<{
        message: string;
    }>;
}

import { ConfigService } from '@nestjs/config';
export declare class EmailService {
    private readonly configService;
    private readonly logger;
    private transporter;
    constructor(configService: ConfigService);
    sendOtpEmail(email: string, otp: string, firstName?: string): Promise<{
        success: boolean;
        message: string;
    }>;
    sendPasswordResetEmail(email: string, resetToken: string, firstName?: string): Promise<{
        success: boolean;
        message: string;
    }>;
    sendWelcomeEmail(email: string, firstName: string): Promise<void>;
    sendRentReminderEmail(email: string, firstName: string, amount: number, dueDate: Date, propertyName: string): Promise<{
        success: boolean;
    }>;
    sendOverdueNoticeEmail(email: string, firstName: string, amount: number, daysOverdue: number, propertyName: string): Promise<{
        success: boolean;
    }>;
    sendMaintenanceUpdateEmail(email: string, firstName: string, requestId: string, status: string, description: string): Promise<{
        success: boolean;
    }>;
    private getOtpEmailTemplate;
    private getPasswordResetTemplate;
    private getWelcomeEmailTemplate;
    private getRentReminderTemplate;
    private getOverdueNoticeTemplate;
    private getMaintenanceUpdateTemplate;
}

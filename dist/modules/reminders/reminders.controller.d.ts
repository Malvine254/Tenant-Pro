import { RemindersService } from './reminders.service';
export declare class RemindersController {
    private readonly remindersService;
    constructor(remindersService: RemindersService);
    sendRentReminder(invoiceId: string): Promise<{
        message: string;
    }>;
    sendMaintenanceUpdate(requestId: string, body: {
        status: string;
        message: string;
    }): Promise<{
        message: string;
    }>;
    sendWelcomeEmail(userId: string): Promise<{
        message: string;
    }>;
}

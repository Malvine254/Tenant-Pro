import { PrismaService } from '../../prisma/prisma.service';
export declare class AnalyticsService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    getTotalRevenue(): Promise<{
        totalRevenue: number;
        successfulPayments: number;
        currency: string;
    }>;
    getOutstandingBalances(): Promise<{
        outstandingInvoices: number;
        outstandingBalance: number;
        currency: string;
    }>;
    getOccupancyRate(): Promise<{
        totalUnits: number;
        occupiedUnits: number;
        vacantUnits: number;
        occupancyRate: number;
        unit: string;
    }>;
    getMonthlyPaymentTrends(months?: number): Promise<{
        months: number;
        currency: string;
        trends: {
            month: string;
            paymentsCount: number;
            totalAmount: number;
        }[];
    }>;
}

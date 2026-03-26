import { AnalyticsService } from './analytics.service';
import { MonthlyTrendQueryDto } from './dto/monthly-trend-query.dto';
export declare class AnalyticsController {
    private readonly analyticsService;
    constructor(analyticsService: AnalyticsService);
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
    getMonthlyPaymentTrends(query: MonthlyTrendQueryDto): Promise<{
        months: number;
        currency: string;
        trends: {
            month: string;
            paymentsCount: number;
            totalAmount: number;
        }[];
    }>;
}

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
exports.AnalyticsService = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const prisma_service_1 = require("../../prisma/prisma.service");
let AnalyticsService = class AnalyticsService {
    constructor(prisma) {
        this.prisma = prisma;
    }
    async getTotalRevenue() {
        const result = await this.prisma.payment.aggregate({
            where: { status: client_1.PaymentStatus.SUCCESS },
            _sum: { amount: true },
            _count: { id: true },
        });
        return {
            totalRevenue: Number(result._sum.amount ?? 0),
            successfulPayments: result._count.id,
            currency: 'KES',
        };
    }
    async getOutstandingBalances() {
        const rows = await this.prisma.$queryRaw `
      SELECT
        COUNT(*) AS outstandingInvoices,
        COALESCE(SUM(totalAmount - paidAmount), 0) AS outstandingBalance
      FROM invoices
      WHERE status <> ${client_1.InvoiceStatus.CANCELLED}
        AND totalAmount > paidAmount
    `;
        const summary = rows[0] ?? { outstandingInvoices: 0, outstandingBalance: 0 };
        return {
            outstandingInvoices: Number(summary.outstandingInvoices ?? 0),
            outstandingBalance: Number(summary.outstandingBalance ?? 0),
            currency: 'KES',
        };
    }
    async getOccupancyRate() {
        const totalUnits = await this.prisma.unit.count();
        const occupiedUnits = await this.prisma.unit.count({
            where: { status: client_1.UnitStatus.OCCUPIED },
        });
        const occupancyRate = totalUnits === 0 ? 0 : Number(((occupiedUnits / totalUnits) * 100).toFixed(2));
        return {
            totalUnits,
            occupiedUnits,
            vacantUnits: totalUnits - occupiedUnits,
            occupancyRate,
            unit: 'percent',
        };
    }
    async getMonthlyPaymentTrends(months = 12) {
        const data = await this.prisma.$queryRaw `
      SELECT
        DATE_FORMAT(paidAt, '%Y-%m') AS month,
        COUNT(*) AS paymentsCount,
        COALESCE(SUM(amount), 0) AS totalAmount
      FROM payments
      WHERE status = ${client_1.PaymentStatus.SUCCESS}
        AND paidAt IS NOT NULL
        AND paidAt >= DATE_SUB(CURDATE(), INTERVAL ${months} MONTH)
      GROUP BY DATE_FORMAT(paidAt, '%Y-%m')
      ORDER BY month ASC
    `;
        return {
            months,
            currency: 'KES',
            trends: data.map((row) => ({
                month: row.month,
                paymentsCount: Number(row.paymentsCount ?? 0),
                totalAmount: Number(row.totalAmount ?? 0),
            })),
        };
    }
};
exports.AnalyticsService = AnalyticsService;
exports.AnalyticsService = AnalyticsService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService])
], AnalyticsService);
//# sourceMappingURL=analytics.service.js.map
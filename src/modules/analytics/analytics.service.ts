import { Injectable } from '@nestjs/common';
import { InvoiceStatus, PaymentStatus, Prisma, UnitStatus } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';

type MonthlyTrendRow = {
  month: string;
  paymentsCount: bigint | number;
  totalAmount: Prisma.Decimal | number | string | null;
};

@Injectable()
export class AnalyticsService {
  constructor(private readonly prisma: PrismaService) {}

  async getTotalRevenue() {
    const result = await this.prisma.payment.aggregate({
      where: { status: PaymentStatus.SUCCESS },
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
    const rows = await this.prisma.$queryRaw<
      Array<{ outstandingInvoices: bigint | number; outstandingBalance: Prisma.Decimal | number | string | null }>
    >`
      SELECT
        COUNT(*) AS outstandingInvoices,
        COALESCE(SUM(totalAmount - paidAmount), 0) AS outstandingBalance
      FROM invoices
      WHERE status <> ${InvoiceStatus.CANCELLED}
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
      where: { status: UnitStatus.OCCUPIED },
    });

    const occupancyRate =
      totalUnits === 0 ? 0 : Number(((occupiedUnits / totalUnits) * 100).toFixed(2));

    return {
      totalUnits,
      occupiedUnits,
      vacantUnits: totalUnits - occupiedUnits,
      occupancyRate,
      unit: 'percent',
    };
  }

  async getMonthlyPaymentTrends(months = 12) {
    const data = await this.prisma.$queryRaw<MonthlyTrendRow[]>`
      SELECT
        DATE_FORMAT(paidAt, '%Y-%m') AS month,
        COUNT(*) AS paymentsCount,
        COALESCE(SUM(amount), 0) AS totalAmount
      FROM payments
      WHERE status = ${PaymentStatus.SUCCESS}
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
}

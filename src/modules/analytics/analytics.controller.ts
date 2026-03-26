import { Controller, Get, Query, UseGuards } from '@nestjs/common';
import { RoleName } from '@prisma/client';
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { AnalyticsService } from './analytics.service';
import { MonthlyTrendQueryDto } from './dto/monthly-trend-query.dto';

@Controller('admin/analytics')
@UseGuards(JwtAuthGuard, RolesGuard)
@Roles(RoleName.ADMIN)
export class AnalyticsController {
  constructor(private readonly analyticsService: AnalyticsService) {}

  @Get('total-revenue')
  getTotalRevenue() {
    return this.analyticsService.getTotalRevenue();
  }

  @Get('outstanding-balances')
  getOutstandingBalances() {
    return this.analyticsService.getOutstandingBalances();
  }

  @Get('occupancy-rate')
  getOccupancyRate() {
    return this.analyticsService.getOccupancyRate();
  }

  @Get('monthly-payment-trends')
  getMonthlyPaymentTrends(@Query() query: MonthlyTrendQueryDto) {
    return this.analyticsService.getMonthlyPaymentTrends(query.months ?? 12);
  }
}

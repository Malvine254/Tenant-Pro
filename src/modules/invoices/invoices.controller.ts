import { Body, Controller, Get, Param, Post, Req, UseGuards } from '@nestjs/common';
import { RoleName } from '@prisma/client';
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { ApplyPenaltiesDto } from './dto/apply-penalties.dto';
import { CreateBillDto } from './dto/create-bill.dto';
import { GenerateRentInvoicesDto } from './dto/generate-rent-invoices.dto';
import { TenantIdParamDto } from './dto/tenant-id-param.dto';
import { InvoicesService } from './invoices.service';

type AuthenticatedRequest = {
  user: {
    userId: string;
    role: RoleName;
    phoneNumber: string;
  };
};

@Controller('invoices')
@UseGuards(JwtAuthGuard, RolesGuard)
export class InvoicesController {
  constructor(private readonly invoicesService: InvoicesService) {}

  @Get()
  @Roles(RoleName.LANDLORD, RoleName.TENANT, RoleName.ADMIN)
  listInvoices(@Req() req: AuthenticatedRequest) {
    return this.invoicesService.listInvoices(req.user.userId, req.user.role);
  }

  @Post('generate-monthly-rent')
  @Roles(RoleName.LANDLORD, RoleName.ADMIN)
  generateMonthlyRent(
    @Req() req: AuthenticatedRequest,
    @Body() dto: GenerateRentInvoicesDto,
  ) {
    return this.invoicesService.generateMonthlyRentInvoices(
      req.user.userId,
      req.user.role,
      dto,
    );
  }

  @Post('bills')
  @Roles(RoleName.LANDLORD, RoleName.ADMIN)
  createBill(@Req() req: AuthenticatedRequest, @Body() dto: CreateBillDto) {
    return this.invoicesService.createUtilityBill(req.user.userId, req.user.role, dto);
  }

  @Post('apply-penalties')
  @Roles(RoleName.LANDLORD, RoleName.ADMIN)
  applyPenalties(@Req() req: AuthenticatedRequest, @Body() dto: ApplyPenaltiesDto) {
    return this.invoicesService.applyOverduePenalties(req.user.userId, req.user.role, dto);
  }

  @Get('tenant/:tenantId')
  @Roles(RoleName.LANDLORD, RoleName.TENANT, RoleName.ADMIN)
  listPerTenant(@Req() req: AuthenticatedRequest, @Param() params: TenantIdParamDto) {
    return this.invoicesService.listInvoicesPerTenant(
      req.user.userId,
      req.user.role,
      params.tenantId,
    );
  }
}

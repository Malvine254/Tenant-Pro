import {
  BadRequestException,
  ForbiddenException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { BillingType, InvoiceStatus, RoleName } from '@prisma/client';
import { Cron } from '@nestjs/schedule';
import { PrismaService } from '../../prisma/prisma.service';
import { ApplyPenaltiesDto } from './dto/apply-penalties.dto';
import { CreateBillDto } from './dto/create-bill.dto';
import { GenerateRentInvoicesDto } from './dto/generate-rent-invoices.dto';

@Injectable()
export class InvoicesService {
  constructor(private readonly prisma: PrismaService) {}

  private toTwoDecimals(value: number) {
    return Number(value.toFixed(2));
  }

  private async assertTenantAccess(
    actorUserId: string,
    actorRole: RoleName,
    tenantId: string,
  ) {
    const tenant = await this.prisma.tenant.findUnique({
      where: { id: tenantId },
      include: {
        unit: {
          include: {
            property: true,
          },
        },
      },
    });

    if (!tenant) {
      throw new NotFoundException('Tenant profile not found');
    }

    if (actorRole === RoleName.ADMIN) {
      return tenant;
    }

    if (actorRole === RoleName.LANDLORD) {
      if (tenant.unit.property.landlordId !== actorUserId) {
        throw new ForbiddenException('You can only access invoices for your tenants');
      }
      return tenant;
    }

    if (actorRole === RoleName.TENANT) {
      if (tenant.userId !== actorUserId) {
        throw new ForbiddenException('You can only access your own invoices');
      }
      return tenant;
    }

    throw new ForbiddenException('Role is not permitted for invoice access');
  }

  async generateMonthlyRentInvoices(
    actorUserId: string,
    actorRole: RoleName,
    dto: GenerateRentInvoicesDto,
  ) {
    const now = new Date();
    const month = dto.month ?? now.getMonth() + 1;
    const year = dto.year ?? now.getFullYear();
    const dueDay = dto.dueDay ?? 5;
    const dueDate = new Date(year, month - 1, dueDay, 23, 59, 59);

    const tenants = await this.prisma.tenant.findMany({
      where: {
        isActive: true,
        ...(actorRole === RoleName.LANDLORD
          ? {
              unit: {
                property: {
                  landlordId: actorUserId,
                },
              },
            }
          : {}),
      },
      include: {
        unit: {
          include: {
            property: true,
          },
        },
      },
    });

    if (tenants.length === 0) {
      return {
        message: 'No active tenants found for invoice generation',
        createdCount: 0,
      };
    }

    const createData = tenants.map((tenant) => ({
      tenantId: tenant.id,
      userId: tenant.userId,
      unitId: tenant.unitId,
      billingType: BillingType.RENT,
      periodMonth: month,
      periodYear: year,
      issueDate: now,
      dueDate,
      amount: tenant.unit.rentAmount,
      penaltyAmount: 0,
      totalAmount: tenant.unit.rentAmount,
      status: InvoiceStatus.PENDING,
    }));

    const result = await this.prisma.invoice.createMany({
      data: createData,
      skipDuplicates: true,
    });

    return {
      message: 'Monthly rent invoice generation completed',
      month,
      year,
      dueDate,
      createdCount: result.count,
    };
  }

  @Cron('0 5 1 * *')
  async runMonthlyRentGenerationCron() {
    await this.generateMonthlyRentInvoices('SYSTEM', RoleName.ADMIN, {});
  }

  async createUtilityBill(actorUserId: string, actorRole: RoleName, dto: CreateBillDto) {
    if (dto.billingType !== BillingType.WATER && dto.billingType !== BillingType.GARBAGE) {
      throw new BadRequestException('Only WATER and GARBAGE bills can be created manually');
    }

    const tenant = await this.assertTenantAccess(actorUserId, actorRole, dto.tenantId);

    try {
      const invoice = await this.prisma.invoice.create({
        data: {
          tenantId: tenant.id,
          userId: tenant.userId,
          unitId: tenant.unitId,
          billingType: dto.billingType,
          periodMonth: dto.periodMonth,
          periodYear: dto.periodYear,
          issueDate: new Date(),
          dueDate: new Date(dto.dueDate),
          amount: dto.amount,
          penaltyAmount: 0,
          totalAmount: dto.amount,
          status: InvoiceStatus.PENDING,
        },
      });

      return invoice;
    } catch {
      throw new BadRequestException(
        'Unable to create bill. A bill for this tenant/type/period may already exist.',
      );
    }
  }

  async applyOverduePenalties(
    actorUserId: string,
    actorRole: RoleName,
    dto: ApplyPenaltiesDto,
  ) {
    const penaltyRatePercent = dto.penaltyRatePercent ?? 10;
    const now = new Date();

    const overdueInvoices = await this.prisma.invoice.findMany({
      where: {
        status: {
          in: [InvoiceStatus.PENDING, InvoiceStatus.OVERDUE],
        },
        paidAt: null,
        dueDate: { lt: now },
        penaltyAmount: 0,
        ...(actorRole === RoleName.LANDLORD
          ? {
              unit: {
                property: {
                  landlordId: actorUserId,
                },
              },
            }
          : {}),
      },
    });

    let updatedCount = 0;

    for (const invoice of overdueInvoices) {
      const amount = Number(invoice.amount);
      const penalty = this.toTwoDecimals((amount * penaltyRatePercent) / 100);
      const total = this.toTwoDecimals(amount + penalty);

      await this.prisma.invoice.update({
        where: { id: invoice.id },
        data: {
          penaltyAmount: penalty,
          totalAmount: total,
          status: InvoiceStatus.OVERDUE,
        },
      });

      updatedCount += 1;
    }

    return {
      message: 'Penalty calculation completed',
      penaltyRatePercent,
      updatedCount,
    };
  }

  async listInvoicesPerTenant(actorUserId: string, actorRole: RoleName, tenantId: string) {
    await this.assertTenantAccess(actorUserId, actorRole, tenantId);

    return this.prisma.invoice.findMany({
      where: { tenantId },
      orderBy: [{ periodYear: 'desc' }, { periodMonth: 'desc' }, { createdAt: 'desc' }],
    });
  }

  async listInvoices(actorUserId: string, actorRole: RoleName) {
    const where =
      actorRole === RoleName.ADMIN
        ? {}
        : actorRole === RoleName.LANDLORD
          ? {
              unit: {
                property: {
                  landlordId: actorUserId,
                },
              },
            }
          : {
              userId: actorUserId,
            };

    return this.prisma.invoice.findMany({
      where,
      include: {
        unit: {
          include: {
            property: true,
          },
        },
        tenant: {
          include: {
            user: true,
          },
        },
      },
      orderBy: [{ periodYear: 'desc' }, { periodMonth: 'desc' }, { createdAt: 'desc' }],
    });
  }
}

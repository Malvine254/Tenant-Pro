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
exports.InvoicesService = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const schedule_1 = require("@nestjs/schedule");
const prisma_service_1 = require("../../prisma/prisma.service");
let InvoicesService = class InvoicesService {
    constructor(prisma) {
        this.prisma = prisma;
    }
    toTwoDecimals(value) {
        return Number(value.toFixed(2));
    }
    async assertTenantAccess(actorUserId, actorRole, tenantId) {
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
            throw new common_1.NotFoundException('Tenant profile not found');
        }
        if (actorRole === client_1.RoleName.ADMIN) {
            return tenant;
        }
        if (actorRole === client_1.RoleName.LANDLORD) {
            if (tenant.unit.property.landlordId !== actorUserId) {
                throw new common_1.ForbiddenException('You can only access invoices for your tenants');
            }
            return tenant;
        }
        if (actorRole === client_1.RoleName.TENANT) {
            if (tenant.userId !== actorUserId) {
                throw new common_1.ForbiddenException('You can only access your own invoices');
            }
            return tenant;
        }
        throw new common_1.ForbiddenException('Role is not permitted for invoice access');
    }
    async generateMonthlyRentInvoices(actorUserId, actorRole, dto) {
        const now = new Date();
        const month = dto.month ?? now.getMonth() + 1;
        const year = dto.year ?? now.getFullYear();
        const dueDay = dto.dueDay ?? 5;
        const dueDate = new Date(year, month - 1, dueDay, 23, 59, 59);
        const tenants = await this.prisma.tenant.findMany({
            where: {
                isActive: true,
                ...(actorRole === client_1.RoleName.LANDLORD
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
            billingType: client_1.BillingType.RENT,
            periodMonth: month,
            periodYear: year,
            issueDate: now,
            dueDate,
            amount: tenant.unit.rentAmount,
            penaltyAmount: 0,
            totalAmount: tenant.unit.rentAmount,
            status: client_1.InvoiceStatus.PENDING,
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
    async runMonthlyRentGenerationCron() {
        await this.generateMonthlyRentInvoices('SYSTEM', client_1.RoleName.ADMIN, {});
    }
    async createUtilityBill(actorUserId, actorRole, dto) {
        if (dto.billingType !== client_1.BillingType.WATER && dto.billingType !== client_1.BillingType.GARBAGE) {
            throw new common_1.BadRequestException('Only WATER and GARBAGE bills can be created manually');
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
                    status: client_1.InvoiceStatus.PENDING,
                },
            });
            return invoice;
        }
        catch {
            throw new common_1.BadRequestException('Unable to create bill. A bill for this tenant/type/period may already exist.');
        }
    }
    async applyOverduePenalties(actorUserId, actorRole, dto) {
        const penaltyRatePercent = dto.penaltyRatePercent ?? 10;
        const now = new Date();
        const overdueInvoices = await this.prisma.invoice.findMany({
            where: {
                status: {
                    in: [client_1.InvoiceStatus.PENDING, client_1.InvoiceStatus.OVERDUE],
                },
                paidAt: null,
                dueDate: { lt: now },
                penaltyAmount: 0,
                ...(actorRole === client_1.RoleName.LANDLORD
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
                    status: client_1.InvoiceStatus.OVERDUE,
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
    async listInvoicesPerTenant(actorUserId, actorRole, tenantId) {
        await this.assertTenantAccess(actorUserId, actorRole, tenantId);
        return this.prisma.invoice.findMany({
            where: { tenantId },
            orderBy: [{ periodYear: 'desc' }, { periodMonth: 'desc' }, { createdAt: 'desc' }],
        });
    }
    async listInvoices(actorUserId, actorRole) {
        const where = actorRole === client_1.RoleName.ADMIN
            ? {}
            : actorRole === client_1.RoleName.LANDLORD
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
};
exports.InvoicesService = InvoicesService;
__decorate([
    (0, schedule_1.Cron)('0 5 1 * *'),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", []),
    __metadata("design:returntype", Promise)
], InvoicesService.prototype, "runMonthlyRentGenerationCron", null);
exports.InvoicesService = InvoicesService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService])
], InvoicesService);
//# sourceMappingURL=invoices.service.js.map
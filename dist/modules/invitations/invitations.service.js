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
exports.InvitationsService = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const crypto_1 = require("crypto");
const prisma_service_1 = require("../../prisma/prisma.service");
let InvitationsService = class InvitationsService {
    constructor(prisma) {
        this.prisma = prisma;
    }
    async generateUniqueCode() {
        for (let attempt = 0; attempt < 5; attempt += 1) {
            const code = (0, crypto_1.randomBytes)(4).toString('hex').toUpperCase();
            const exists = await this.prisma.invitation.findUnique({ where: { code } });
            if (!exists) {
                return code;
            }
        }
        throw new common_1.BadRequestException('Unable to generate a unique invitation code');
    }
    async createInvitation(actorUserId, actorRole, dto) {
        const property = await this.prisma.property.findUnique({
            where: { id: dto.propertyId },
        });
        if (!property) {
            throw new common_1.NotFoundException('Property not found');
        }
        if (actorRole !== client_1.RoleName.ADMIN && property.landlordId !== actorUserId) {
            throw new common_1.ForbiddenException('You can only invite tenants to your own properties');
        }
        const unit = await this.prisma.unit.findUnique({
            where: { id: dto.unitId },
        });
        if (!unit || unit.propertyId !== dto.propertyId) {
            throw new common_1.NotFoundException('Unit not found for the selected property');
        }
        const code = await this.generateUniqueCode();
        const expiryHours = dto.expiresInHours ?? 72;
        const expiresAt = new Date(Date.now() + expiryHours * 60 * 60 * 1000);
        const invitation = await this.prisma.invitation.create({
            data: {
                code,
                propertyId: dto.propertyId,
                unitId: dto.unitId,
                sentById: actorUserId,
                phoneNumber: dto.phoneNumber,
                sentVia: dto.sentVia,
                expiresAt,
                status: client_1.InvitationStatus.PENDING,
            },
        });
        return invitation;
    }
    async acceptInvitation(userId, dto) {
        const invitation = await this.prisma.invitation.findUnique({
            where: { code: dto.code },
            include: {
                property: true,
                unit: true,
            },
        });
        if (!invitation) {
            throw new common_1.NotFoundException('Invitation not found');
        }
        if (invitation.status !== client_1.InvitationStatus.PENDING) {
            throw new common_1.BadRequestException('Invitation is no longer pending');
        }
        if (invitation.expiresAt.getTime() < Date.now()) {
            await this.prisma.invitation.update({
                where: { id: invitation.id },
                data: { status: client_1.InvitationStatus.EXPIRED },
            });
            throw new common_1.BadRequestException('Invitation has expired');
        }
        const user = await this.prisma.user.findUnique({
            where: { id: userId },
            include: { role: true },
        });
        if (!user) {
            throw new common_1.NotFoundException('User not found');
        }
        if (user.role.name !== client_1.RoleName.TENANT) {
            throw new common_1.ForbiddenException('Only users with TENANT role can accept invitations');
        }
        if (user.phoneNumber !== invitation.phoneNumber) {
            throw new common_1.ForbiddenException('Invitation phone number does not match your account');
        }
        const activeTenantOnUnit = await this.prisma.tenant.findFirst({
            where: {
                unitId: invitation.unitId,
                isActive: true,
            },
        });
        if (activeTenantOnUnit) {
            throw new common_1.BadRequestException('This unit is already occupied by an active tenant');
        }
        const result = await this.prisma.$transaction(async (tx) => {
            const tenant = await tx.tenant.upsert({
                where: { userId: user.id },
                update: {
                    unitId: invitation.unitId,
                    isActive: true,
                    moveInDate: new Date(),
                    moveOutDate: null,
                },
                create: {
                    userId: user.id,
                    unitId: invitation.unitId,
                    isActive: true,
                    moveInDate: new Date(),
                },
            });
            await tx.unit.update({
                where: { id: invitation.unitId },
                data: { status: client_1.UnitStatus.OCCUPIED },
            });
            const acceptedInvitation = await tx.invitation.update({
                where: { id: invitation.id },
                data: {
                    status: client_1.InvitationStatus.ACCEPTED,
                    acceptedAt: new Date(),
                },
            });
            return { tenant, invitation: acceptedInvitation };
        });
        return result;
    }
    async expirePendingInvitations(actorUserId, actorRole) {
        const now = new Date();
        const whereClause = actorRole === client_1.RoleName.ADMIN
            ? {
                status: client_1.InvitationStatus.PENDING,
                expiresAt: { lt: now },
            }
            : {
                status: client_1.InvitationStatus.PENDING,
                expiresAt: { lt: now },
                property: {
                    landlordId: actorUserId,
                },
            };
        const result = await this.prisma.invitation.updateMany({
            where: whereClause,
            data: {
                status: client_1.InvitationStatus.EXPIRED,
            },
        });
        return {
            message: 'Expired invitations processed successfully',
            updatedCount: result.count,
        };
    }
};
exports.InvitationsService = InvitationsService;
exports.InvitationsService = InvitationsService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService])
], InvitationsService);
//# sourceMappingURL=invitations.service.js.map
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
exports.PropertiesService = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const prisma_service_1 = require("../../prisma/prisma.service");
let PropertiesService = class PropertiesService {
    constructor(prisma) {
        this.prisma = prisma;
    }
    async createProperty(actorUserId, actorRole, dto) {
        let landlordId = actorUserId;
        if (actorRole === client_1.RoleName.ADMIN) {
            if (!dto.landlordId) {
                throw new common_1.ForbiddenException('Admin must provide landlordId when creating a property');
            }
            const landlord = await this.prisma.user.findUnique({
                where: { id: dto.landlordId },
                include: { role: true },
            });
            if (!landlord || landlord.role.name !== client_1.RoleName.LANDLORD) {
                throw new common_1.NotFoundException('Valid landlord user was not found for landlordId');
            }
            landlordId = dto.landlordId;
        }
        const property = await this.prisma.property.create({
            data: {
                landlordId,
                name: dto.name,
                description: dto.description,
                coverImageUrl: dto.coverImageUrl,
                addressLine: dto.addressLine,
                city: dto.city,
                state: dto.state,
                country: dto.country,
            },
            include: {
                units: true,
            },
        });
        return property;
    }
    async addUnit(actorUserId, actorRole, propertyId, dto) {
        const property = await this.prisma.property.findUnique({
            where: { id: propertyId },
        });
        if (!property) {
            throw new common_1.NotFoundException('Property not found');
        }
        if (actorRole !== client_1.RoleName.ADMIN && property.landlordId !== actorUserId) {
            throw new common_1.ForbiddenException('You can only add units to your own properties');
        }
        return this.prisma.unit.create({
            data: {
                propertyId,
                unitNumber: dto.unitNumber,
                floor: dto.floor,
                rentAmount: dto.rentAmount,
                status: dto.status,
                imageUrls: dto.imageUrls,
            },
        });
    }
    async updateProperty(actorUserId, actorRole, propertyId, dto) {
        const property = await this.prisma.property.findUnique({
            where: { id: propertyId },
            include: { landlord: { include: { role: true } } },
        });
        if (!property) {
            throw new common_1.NotFoundException('Property not found');
        }
        if (actorRole !== client_1.RoleName.ADMIN && property.landlordId !== actorUserId) {
            throw new common_1.ForbiddenException('You can only update your own properties');
        }
        let landlordId = property.landlordId;
        if (actorRole === client_1.RoleName.ADMIN && dto.landlordId) {
            const landlord = await this.prisma.user.findUnique({
                where: { id: dto.landlordId },
                include: { role: true },
            });
            if (!landlord || landlord.role.name !== client_1.RoleName.LANDLORD) {
                throw new common_1.NotFoundException('Valid landlord user was not found for landlordId');
            }
            landlordId = dto.landlordId;
        }
        return this.prisma.property.update({
            where: { id: propertyId },
            data: {
                landlordId,
                name: dto.name,
                description: dto.description,
                coverImageUrl: dto.coverImageUrl,
                addressLine: dto.addressLine,
                city: dto.city,
                state: dto.state,
                country: dto.country,
            },
            include: {
                landlord: { include: { role: true } },
                units: { orderBy: { unitNumber: 'asc' } },
            },
        });
    }
    async updateUnit(actorUserId, actorRole, unitId, dto) {
        const unit = await this.prisma.unit.findUnique({
            where: { id: unitId },
            include: { property: true },
        });
        if (!unit) {
            throw new common_1.NotFoundException('Unit not found');
        }
        if (actorRole !== client_1.RoleName.ADMIN && unit.property.landlordId !== actorUserId) {
            throw new common_1.ForbiddenException('You can only update units in your own properties');
        }
        return this.prisma.unit.update({
            where: { id: unitId },
            data: {
                unitNumber: dto.unitNumber,
                floor: dto.floor,
                rentAmount: dto.rentAmount,
                status: dto.status,
                imageUrls: dto.imageUrls,
            },
        });
    }
    async listProperties(actorUserId, actorRole) {
        return this.prisma.property.findMany({
            where: actorRole === client_1.RoleName.ADMIN ? {} : { landlordId: actorUserId },
            include: {
                landlord: {
                    include: {
                        role: true,
                    },
                },
                units: {
                    orderBy: { unitNumber: 'asc' },
                },
            },
            orderBy: {
                createdAt: 'desc',
            },
        });
    }
};
exports.PropertiesService = PropertiesService;
exports.PropertiesService = PropertiesService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService])
], PropertiesService);
//# sourceMappingURL=properties.service.js.map
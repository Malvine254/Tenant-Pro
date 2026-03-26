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
const prisma_service_1 = require("../../prisma/prisma.service");
let PropertiesService = class PropertiesService {
    constructor(prisma) {
        this.prisma = prisma;
    }
    async createProperty(landlordId, dto) {
        const property = await this.prisma.property.create({
            data: {
                landlordId,
                name: dto.name,
                description: dto.description,
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
    async addUnit(landlordId, propertyId, dto) {
        const property = await this.prisma.property.findUnique({
            where: { id: propertyId },
        });
        if (!property) {
            throw new common_1.NotFoundException('Property not found');
        }
        if (property.landlordId !== landlordId) {
            throw new common_1.ForbiddenException('You can only add units to your own properties');
        }
        return this.prisma.unit.create({
            data: {
                propertyId,
                unitNumber: dto.unitNumber,
                floor: dto.floor,
                rentAmount: dto.rentAmount,
            },
        });
    }
    async listLandlordProperties(landlordId) {
        return this.prisma.property.findMany({
            where: { landlordId },
            include: {
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
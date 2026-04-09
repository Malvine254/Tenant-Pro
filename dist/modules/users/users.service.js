"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.UsersService = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const bcrypt = __importStar(require("bcryptjs"));
const prisma_service_1 = require("../../prisma/prisma.service");
let UsersService = class UsersService {
    constructor(prisma) {
        this.prisma = prisma;
    }
    toUserResponse(user) {
        return {
            id: user.id,
            phoneNumber: user.phoneNumber,
            email: user.email,
            firstName: user.firstName,
            lastName: user.lastName,
            fullName: [user.firstName, user.lastName].filter(Boolean).join(' ').trim(),
            profileImageUrl: user.profileImageUrl ?? null,
            emergencyContactName: user.emergencyContactName ?? null,
            emergencyContactPhone: user.emergencyContactPhone ?? null,
            bio: user.bio ?? null,
            role: user.role.name,
            isActive: user.isActive,
            createdAt: user.createdAt,
            updatedAt: user.updatedAt,
        };
    }
    async getRoleIdByName(roleName) {
        const role = await this.prisma.role.findUnique({
            where: { name: roleName },
        });
        if (!role) {
            throw new common_1.NotFoundException(`Role ${roleName} was not found. Seed roles first.`);
        }
        return role.id;
    }
    normalizeEmail(email) {
        const normalized = email?.trim().toLowerCase();
        return normalized ? normalized : null;
    }
    rethrowUniqueConstraint(error) {
        if (error instanceof client_1.Prisma.PrismaClientKnownRequestError &&
            error.code === 'P2002') {
            const target = Array.isArray(error.meta?.target)
                ? error.meta.target.join(',')
                : String(error.meta?.target ?? '');
            if (target.includes('email')) {
                throw new common_1.ConflictException('An account with this email already exists');
            }
            if (target.includes('phoneNumber')) {
                throw new common_1.ConflictException('A user with this phone number already exists');
            }
            throw new common_1.ConflictException('A user with these details already exists');
        }
        throw error;
    }
    async create(dto) {
        const existingUser = await this.prisma.user.findUnique({
            where: { phoneNumber: dto.phoneNumber },
        });
        if (existingUser) {
            throw new common_1.ConflictException('A user with this phone number already exists');
        }
        const normalizedEmail = this.normalizeEmail(dto.email);
        if (normalizedEmail) {
            const existingEmailUser = await this.prisma.user.findUnique({
                where: { email: normalizedEmail },
            });
            if (existingEmailUser) {
                throw new common_1.ConflictException('An account with this email already exists');
            }
        }
        const roleId = await this.getRoleIdByName(dto.role);
        const passwordHash = dto.password ? await bcrypt.hash(dto.password, 10) : null;
        try {
            const user = await this.prisma.user.create({
                data: {
                    phoneNumber: dto.phoneNumber,
                    email: normalizedEmail,
                    firstName: dto.firstName,
                    lastName: dto.lastName,
                    roleId,
                    passwordHash,
                },
                include: {
                    role: true,
                },
            });
            return this.toUserResponse(user);
        }
        catch (error) {
            this.rethrowUniqueConstraint(error);
        }
    }
    async register(dto) {
        return this.create(dto);
    }
    async findAll() {
        const users = await this.prisma.user.findMany({
            include: {
                role: true,
            },
            orderBy: {
                createdAt: 'desc',
            },
        });
        return users.map((user) => this.toUserResponse(user));
    }
    async findOne(userId) {
        const user = await this.prisma.user.findUnique({
            where: { id: userId },
            include: {
                role: true,
            },
        });
        if (!user) {
            throw new common_1.NotFoundException('User not found');
        }
        return this.toUserResponse(user);
    }
    async update(userId, dto) {
        await this.findOne(userId);
        const data = {};
        if (dto.phoneNumber !== undefined)
            data.phoneNumber = dto.phoneNumber;
        if (dto.email !== undefined)
            data.email = this.normalizeEmail(dto.email);
        if (dto.firstName !== undefined)
            data.firstName = dto.firstName;
        if (dto.lastName !== undefined)
            data.lastName = dto.lastName;
        if (dto.isActive !== undefined)
            data.isActive = dto.isActive;
        if (dto.password !== undefined) {
            data.passwordHash = dto.password ? await bcrypt.hash(dto.password, 10) : null;
        }
        if (dto.role !== undefined) {
            data.roleId = await this.getRoleIdByName(dto.role);
        }
        try {
            const user = await this.prisma.user.update({
                where: { id: userId },
                data,
                include: {
                    role: true,
                },
            });
            return this.toUserResponse(user);
        }
        catch (error) {
            this.rethrowUniqueConstraint(error);
            throw new common_1.BadRequestException('Unable to update user. Check the submitted values.');
        }
    }
    async remove(userId) {
        await this.findOne(userId);
        try {
            await this.prisma.user.delete({ where: { id: userId } });
            return { message: 'User deleted successfully' };
        }
        catch (error) {
            throw new common_1.BadRequestException('Unable to delete user. User may be referenced by related records.');
        }
    }
    async assignRole(userId, dto) {
        const roleId = await this.getRoleIdByName(dto.role);
        const user = await this.prisma.user.update({
            where: { id: userId },
            data: { roleId },
            include: { role: true },
        });
        return this.toUserResponse(user);
    }
    async getProfile(userId) {
        const user = await this.prisma.user.findUnique({
            where: { id: userId },
            include: {
                role: true,
                tenantProfile: {
                    include: {
                        unit: {
                            include: {
                                property: true,
                            },
                        },
                    },
                },
            },
        });
        if (!user) {
            throw new common_1.NotFoundException('User not found');
        }
        return {
            ...this.toUserResponse(user),
            tenantProfile: user.tenantProfile
                ? {
                    id: user.tenantProfile.id,
                    moveInDate: user.tenantProfile.moveInDate,
                    moveOutDate: user.tenantProfile.moveOutDate,
                    isActive: user.tenantProfile.isActive,
                    unit: {
                        id: user.tenantProfile.unit.id,
                        unitNumber: user.tenantProfile.unit.unitNumber,
                        floor: user.tenantProfile.unit.floor,
                        rentAmount: Number(user.tenantProfile.unit.rentAmount),
                        imageUrls: user.tenantProfile.unit.imageUrls ?? [],
                        property: {
                            id: user.tenantProfile.unit.property.id,
                            name: user.tenantProfile.unit.property.name,
                            description: user.tenantProfile.unit.property.description,
                            coverImageUrl: user.tenantProfile.unit.property.coverImageUrl,
                            addressLine: user.tenantProfile.unit.property.addressLine,
                            city: user.tenantProfile.unit.property.city,
                            state: user.tenantProfile.unit.property.state,
                            country: user.tenantProfile.unit.property.country,
                        },
                    },
                }
                : null,
        };
    }
    async updateProfile(userId, dto) {
        await this.findOne(userId);
        const data = {
            phoneNumber: dto.phoneNumber,
            email: dto.email !== undefined ? this.normalizeEmail(dto.email) : undefined,
            firstName: dto.firstName,
            lastName: dto.lastName,
            emergencyContactName: dto.emergencyContactName,
            emergencyContactPhone: dto.emergencyContactPhone,
            bio: dto.bio,
            profileImageUrl: dto.profileImageUrl,
        };
        if (dto.password !== undefined) {
            data.passwordHash = dto.password ? await bcrypt.hash(dto.password, 10) : null;
        }
        try {
            await this.prisma.user.update({
                where: { id: userId },
                data,
            });
            return this.getProfile(userId);
        }
        catch (error) {
            this.rethrowUniqueConstraint(error);
            throw new common_1.BadRequestException('Unable to update profile. Check the submitted values.');
        }
    }
    async findByPhoneNumber(phoneNumber) {
        return this.prisma.user.findUnique({
            where: { phoneNumber },
            include: { role: true },
        });
    }
    async findByEmail(email) {
        return this.prisma.user.findUnique({
            where: { email: email.trim().toLowerCase() },
            include: { role: true },
        });
    }
    async findById(userId) {
        return this.prisma.user.findUnique({
            where: { id: userId },
            include: { role: true },
        });
    }
    isAllowedAppRole(role) {
        return (role === client_1.RoleName.LANDLORD ||
            role === client_1.RoleName.TENANT ||
            role === client_1.RoleName.ADMIN ||
            role === client_1.RoleName.CARETAKER);
    }
    async updatePassword(userId, hashedPassword) {
        return this.prisma.user.update({
            where: { id: userId },
            data: { passwordHash: hashedPassword },
        });
    }
};
exports.UsersService = UsersService;
exports.UsersService = UsersService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService])
], UsersService);
//# sourceMappingURL=users.service.js.map
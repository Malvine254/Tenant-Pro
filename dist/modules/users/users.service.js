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
const config_1 = require("@nestjs/config");
const client_1 = require("@prisma/client");
const bcrypt = __importStar(require("bcryptjs"));
const fs_1 = require("fs");
const path_1 = require("path");
const crypto_1 = require("crypto");
const prisma_service_1 = require("../../prisma/prisma.service");
let UsersService = class UsersService {
    constructor(prisma, configService) {
        this.prisma = prisma;
        this.configService = configService;
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
            emailVerified: user.emailVerified ?? true,
            createdAt: user.createdAt,
            updatedAt: user.updatedAt,
        };
    }
    isJsonDbMode() {
        return this.configService.get('USE_JSON_DB', 'false').toLowerCase() === 'true';
    }
    getJsonDbPath() {
        return (0, path_1.join)(process.cwd(), 'data', 'json-db.json');
    }
    normalizeEmail(email) {
        const normalized = email?.trim().toLowerCase();
        return normalized ? normalized : null;
    }
    generateJsonPhoneNumber(existingUsers) {
        let phoneNumber = '';
        do {
            phoneNumber = `+25479${Math.floor(1000000 + Math.random() * 8999999)}`;
        } while (existingUsers.some((user) => user.phoneNumber === phoneNumber));
        return phoneNumber;
    }
    async ensureJsonDb() {
        const dataDir = (0, path_1.join)(process.cwd(), 'data');
        const dbPath = this.getJsonDbPath();
        await fs_1.promises.mkdir(dataDir, { recursive: true });
        try {
            await fs_1.promises.access(dbPath);
        }
        catch {
            const now = new Date().toISOString();
            const seedUsers = [
                {
                    id: 'user-landlord-001',
                    phoneNumber: this.configService.get('SEED_LANDLORD_PHONE', '+254700000001'),
                    email: this.normalizeEmail(this.configService.get('SEED_LANDLORD_EMAIL', 'landlord@example.com')),
                    firstName: 'Starmax',
                    lastName: 'Landlord',
                    passwordHash: null,
                    role: client_1.RoleName.LANDLORD,
                    isActive: true,
                    profileImageUrl: null,
                    emergencyContactName: null,
                    emergencyContactPhone: null,
                    bio: 'Landlord demo account for JSON mode.',
                    emailVerified: true,
                    createdAt: now,
                    updatedAt: now,
                },
                {
                    id: 'user-admin-001',
                    phoneNumber: this.configService.get('SEED_ADMIN_PHONE', '+254700000099'),
                    email: this.normalizeEmail(this.configService.get('SEED_ADMIN_EMAIL', 'admin@example.com')),
                    firstName: 'Starmax',
                    lastName: 'Admin',
                    passwordHash: null,
                    role: client_1.RoleName.ADMIN,
                    isActive: true,
                    profileImageUrl: null,
                    emergencyContactName: null,
                    emergencyContactPhone: null,
                    bio: 'Admin demo account for JSON mode.',
                    emailVerified: true,
                    createdAt: now,
                    updatedAt: now,
                },
                {
                    id: 'user-tenant-001',
                    phoneNumber: this.configService.get('SEED_TENANT_PHONE', '+254700000010'),
                    email: this.normalizeEmail(this.configService.get('SEED_TENANT_EMAIL', 'tenant@example.com')),
                    firstName: 'Starmax',
                    lastName: 'Tenant',
                    passwordHash: null,
                    role: client_1.RoleName.TENANT,
                    isActive: true,
                    profileImageUrl: null,
                    emergencyContactName: null,
                    emergencyContactPhone: null,
                    bio: 'Tenant demo account for JSON mode.',
                    emailVerified: true,
                    createdAt: now,
                    updatedAt: now,
                },
                {
                    id: 'user-caretaker-001',
                    phoneNumber: this.configService.get('SEED_CARETAKER_PHONE', '+254700000020'),
                    email: this.normalizeEmail(this.configService.get('SEED_CARETAKER_EMAIL', 'caretaker@example.com')),
                    firstName: 'Starmax',
                    lastName: 'Caretaker',
                    passwordHash: null,
                    role: client_1.RoleName.CARETAKER,
                    isActive: true,
                    profileImageUrl: null,
                    emergencyContactName: null,
                    emergencyContactPhone: null,
                    bio: 'Caretaker demo account for JSON mode.',
                    emailVerified: true,
                    createdAt: now,
                    updatedAt: now,
                },
            ];
            await this.writeJsonDb({ users: seedUsers, companyInquiries: [], demoAccessRequests: [] });
        }
    }
    async readJsonDb() {
        await this.ensureJsonDb();
        const raw = await fs_1.promises.readFile(this.getJsonDbPath(), 'utf8');
        const parsed = JSON.parse(raw);
        return {
            users: parsed.users ?? [],
            companyInquiries: parsed.companyInquiries ?? [],
            demoAccessRequests: parsed.demoAccessRequests ?? [],
        };
    }
    async writeJsonDb(db) {
        await fs_1.promises.writeFile(this.getJsonDbPath(), JSON.stringify(db, null, 2), 'utf8');
    }
    mapJsonUser(user) {
        return {
            id: user.id,
            phoneNumber: user.phoneNumber,
            email: user.email,
            firstName: user.firstName,
            lastName: user.lastName,
            passwordHash: user.passwordHash,
            profileImageUrl: user.profileImageUrl ?? null,
            emergencyContactName: user.emergencyContactName ?? null,
            emergencyContactPhone: user.emergencyContactPhone ?? null,
            bio: user.bio ?? null,
            isActive: user.isActive,
            emailVerified: user.emailVerified ?? true,
            createdAt: new Date(user.createdAt),
            updatedAt: new Date(user.updatedAt),
            role: { name: user.role },
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
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            if (db.users.some((user) => user.phoneNumber === dto.phoneNumber)) {
                throw new common_1.ConflictException('A user with this phone number already exists');
            }
            const normalizedEmail = this.normalizeEmail(dto.email);
            if (normalizedEmail && db.users.some((user) => user.email === normalizedEmail)) {
                throw new common_1.ConflictException('An account with this email already exists');
            }
            const phoneNumber = dto.phoneNumber ?? this.generateJsonPhoneNumber(db.users);
            const role = dto.role ?? client_1.RoleName.LANDLORD;
            const now = new Date().toISOString();
            const user = {
                id: (0, crypto_1.randomUUID)(),
                phoneNumber,
                email: normalizedEmail,
                firstName: dto.firstName ?? null,
                lastName: dto.lastName ?? null,
                passwordHash: dto.password ? await bcrypt.hash(dto.password, 10) : null,
                role,
                isActive: normalizedEmail ? false : true,
                emailVerified: normalizedEmail ? false : true,
                profileImageUrl: null,
                emergencyContactName: null,
                emergencyContactPhone: null,
                bio: null,
                createdAt: now,
                updatedAt: now,
            };
            db.users.unshift(user);
            await this.writeJsonDb(db);
            return this.toUserResponse(this.mapJsonUser(user));
        }
        const phoneNumber = dto.phoneNumber ?? `+25479${Math.floor(1000000 + Math.random() * 8999999)}`;
        const existingUser = await this.prisma.user.findUnique({
            where: { phoneNumber },
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
        const roleId = await this.getRoleIdByName(dto.role ?? client_1.RoleName.LANDLORD);
        const passwordHash = dto.password ? await bcrypt.hash(dto.password, 10) : null;
        try {
            const user = await this.prisma.user.create({
                data: {
                    phoneNumber,
                    email: normalizedEmail,
                    firstName: dto.firstName,
                    lastName: dto.lastName,
                    roleId,
                    passwordHash,
                    isActive: normalizedEmail ? false : true,
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
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            return db.users
                .slice()
                .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
                .map((user) => this.toUserResponse(this.mapJsonUser(user)));
        }
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
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            const user = db.users.find((item) => item.id === userId);
            if (!user) {
                throw new common_1.NotFoundException('User not found');
            }
            return this.toUserResponse(this.mapJsonUser(user));
        }
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
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            const userIndex = db.users.findIndex((item) => item.id === userId);
            if (userIndex === -1) {
                throw new common_1.NotFoundException('User not found');
            }
            const existing = db.users[userIndex];
            const normalizedEmail = dto.email !== undefined ? this.normalizeEmail(dto.email) : existing.email;
            if (dto.phoneNumber &&
                db.users.some((user) => user.id !== userId && user.phoneNumber === dto.phoneNumber)) {
                throw new common_1.ConflictException('A user with this phone number already exists');
            }
            if (normalizedEmail &&
                db.users.some((user) => user.id !== userId && user.email === normalizedEmail)) {
                throw new common_1.ConflictException('An account with this email already exists');
            }
            const updated = {
                ...existing,
                phoneNumber: dto.phoneNumber ?? existing.phoneNumber,
                email: normalizedEmail,
                firstName: dto.firstName !== undefined ? dto.firstName : existing.firstName,
                lastName: dto.lastName !== undefined ? dto.lastName : existing.lastName,
                isActive: dto.isActive ?? existing.isActive,
                role: dto.role ?? existing.role,
                updatedAt: new Date().toISOString(),
            };
            if (dto.password !== undefined) {
                updated.passwordHash = dto.password ? await bcrypt.hash(dto.password, 10) : null;
            }
            db.users[userIndex] = updated;
            await this.writeJsonDb(db);
            return this.toUserResponse(this.mapJsonUser(updated));
        }
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
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            const before = db.users.length;
            db.users = db.users.filter((user) => user.id !== userId);
            if (db.users.length === before) {
                throw new common_1.NotFoundException('User not found');
            }
            await this.writeJsonDb(db);
            return { message: 'User deleted successfully' };
        }
        await this.findOne(userId);
        try {
            await this.prisma.user.delete({ where: { id: userId } });
            return { message: 'User deleted successfully' };
        }
        catch {
            throw new common_1.BadRequestException('Unable to delete user. User may be referenced by related records.');
        }
    }
    async assignRole(userId, dto) {
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            const userIndex = db.users.findIndex((item) => item.id === userId);
            if (userIndex === -1) {
                throw new common_1.NotFoundException('User not found');
            }
            db.users[userIndex] = {
                ...db.users[userIndex],
                role: dto.role,
                updatedAt: new Date().toISOString(),
            };
            await this.writeJsonDb(db);
            return this.toUserResponse(this.mapJsonUser(db.users[userIndex]));
        }
        const roleId = await this.getRoleIdByName(dto.role);
        const user = await this.prisma.user.update({
            where: { id: userId },
            data: { roleId },
            include: { role: true },
        });
        return this.toUserResponse(user);
    }
    async getProfile(userId) {
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            const user = db.users.find((item) => item.id === userId);
            if (!user) {
                throw new common_1.NotFoundException('User not found');
            }
            return {
                ...this.toUserResponse(this.mapJsonUser(user)),
                tenantProfile: null,
            };
        }
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
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            const userIndex = db.users.findIndex((item) => item.id === userId);
            if (userIndex === -1) {
                throw new common_1.NotFoundException('User not found');
            }
            const existing = db.users[userIndex];
            const normalizedEmail = dto.email !== undefined ? this.normalizeEmail(dto.email) : existing.email;
            if (normalizedEmail &&
                db.users.some((user) => user.id !== userId && user.email === normalizedEmail)) {
                throw new common_1.ConflictException('An account with this email already exists');
            }
            const updated = {
                ...existing,
                phoneNumber: dto.phoneNumber ?? existing.phoneNumber,
                email: normalizedEmail,
                firstName: dto.firstName !== undefined ? dto.firstName : existing.firstName,
                lastName: dto.lastName !== undefined ? dto.lastName : existing.lastName,
                emergencyContactName: dto.emergencyContactName !== undefined
                    ? dto.emergencyContactName
                    : existing.emergencyContactName,
                emergencyContactPhone: dto.emergencyContactPhone !== undefined
                    ? dto.emergencyContactPhone
                    : existing.emergencyContactPhone,
                bio: dto.bio !== undefined ? dto.bio : existing.bio,
                profileImageUrl: dto.profileImageUrl !== undefined ? dto.profileImageUrl : existing.profileImageUrl,
                updatedAt: new Date().toISOString(),
            };
            if (dto.password !== undefined) {
                updated.passwordHash = dto.password ? await bcrypt.hash(dto.password, 10) : null;
            }
            db.users[userIndex] = updated;
            await this.writeJsonDb(db);
            return this.getProfile(userId);
        }
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
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            const user = db.users.find((item) => item.phoneNumber === phoneNumber);
            return user ? this.mapJsonUser(user) : null;
        }
        return this.prisma.user.findUnique({
            where: { phoneNumber },
            include: { role: true },
        });
    }
    async findByEmail(email) {
        const normalizedEmail = email.trim().toLowerCase();
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            const user = db.users.find((item) => item.email === normalizedEmail);
            return user ? this.mapJsonUser(user) : null;
        }
        return this.prisma.user.findUnique({
            where: { email: normalizedEmail },
            include: { role: true },
        });
    }
    async findById(userId) {
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            const user = db.users.find((item) => item.id === userId);
            return user ? this.mapJsonUser(user) : null;
        }
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
    async activateAndVerifyUser(userId) {
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            const userIndex = db.users.findIndex((item) => item.id === userId);
            if (userIndex === -1) {
                throw new common_1.NotFoundException('User not found');
            }
            db.users[userIndex] = {
                ...db.users[userIndex],
                isActive: true,
                emailVerified: true,
                updatedAt: new Date().toISOString(),
            };
            await this.writeJsonDb(db);
            return this.mapJsonUser(db.users[userIndex]);
        }
        return this.prisma.user.update({
            where: { id: userId },
            data: { isActive: true },
        });
    }
    async getDemoAccessExpiry(loginEmail) {
        if (!this.isJsonDbMode()) {
            return null;
        }
        const normalizedEmail = this.normalizeEmail(loginEmail);
        const db = await this.readJsonDb();
        const match = (db.demoAccessRequests ?? [])
            .filter((item) => item.loginEmail === normalizedEmail)
            .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())[0];
        return match ? new Date(match.expiresAt).getTime() : null;
    }
    async storeDemoAccessRequest(entry) {
        if (!this.isJsonDbMode()) {
            return;
        }
        const db = await this.readJsonDb();
        const record = {
            recipientEmail: this.normalizeEmail(entry.recipientEmail) ?? entry.recipientEmail,
            loginEmail: this.normalizeEmail(entry.loginEmail) ?? entry.loginEmail,
            expiresAt: entry.expiresAt.toISOString(),
            createdAt: new Date().toISOString(),
            name: entry.name?.trim() || null,
            userId: entry.userId ?? null,
        };
        db.demoAccessRequests = [record, ...(db.demoAccessRequests ?? []).filter((item) => item.loginEmail !== record.loginEmail)].slice(0, 100);
        await this.writeJsonDb(db);
    }
    async updatePassword(userId, hashedPassword) {
        if (this.isJsonDbMode()) {
            const db = await this.readJsonDb();
            const userIndex = db.users.findIndex((item) => item.id === userId);
            if (userIndex === -1) {
                throw new common_1.NotFoundException('User not found');
            }
            db.users[userIndex] = {
                ...db.users[userIndex],
                passwordHash: hashedPassword,
                updatedAt: new Date().toISOString(),
            };
            await this.writeJsonDb(db);
            return this.mapJsonUser(db.users[userIndex]);
        }
        return this.prisma.user.update({
            where: { id: userId },
            data: { passwordHash: hashedPassword },
        });
    }
};
exports.UsersService = UsersService;
exports.UsersService = UsersService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService,
        config_1.ConfigService])
], UsersService);
//# sourceMappingURL=users.service.js.map
import {
  BadRequestException,
  ConflictException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { Prisma, RoleName } from '@prisma/client';
import * as bcrypt from 'bcryptjs';
import { promises as fs } from 'fs';
import { join } from 'path';
import { randomUUID } from 'crypto';
import { PrismaService } from '../../prisma/prisma.service';
import { AssignRoleDto } from './dto/assign-role.dto';
import { CreateUserDto } from './dto/create-user.dto';
import { RegisterUserDto } from './dto/register-user.dto';
import { UpdateProfileDto } from './dto/update-profile.dto';
import { UpdateUserDto } from './dto/update-user.dto';

type JsonUserRecord = {
  id: string;
  phoneNumber: string;
  email: string | null;
  firstName: string | null;
  lastName: string | null;
  passwordHash: string | null;
  profileImageUrl?: string | null;
  emergencyContactName?: string | null;
  emergencyContactPhone?: string | null;
  bio?: string | null;
  isActive: boolean;
  emailVerified?: boolean;
  role: RoleName;
  createdAt: string;
  updatedAt: string;
};

type DemoAccessRequestRecord = {
  recipientEmail: string;
  loginEmail: string;
  expiresAt: string;
  createdAt: string;
  name?: string | null;
  userId?: string | null;
};

type JsonDb = {
  users: JsonUserRecord[];
  companyInquiries?: unknown[];
  demoAccessRequests?: DemoAccessRequestRecord[];
};

@Injectable()
export class UsersService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly configService: ConfigService,
  ) {}

  private toUserResponse(user: {
    id: string;
    phoneNumber: string;
    email: string | null;
    firstName: string | null;
    lastName: string | null;
    profileImageUrl?: string | null;
    emergencyContactName?: string | null;
    emergencyContactPhone?: string | null;
    bio?: string | null;
    isActive: boolean;
    emailVerified?: boolean;
    createdAt: Date;
    updatedAt: Date;
    role: { name: RoleName };
  }) {
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

  private isJsonDbMode() {
    return this.configService.get<string>('USE_JSON_DB', 'false').toLowerCase() === 'true';
  }

  private getJsonDbPath() {
    return join(process.cwd(), 'data', 'json-db.json');
  }

  private normalizeEmail(email?: string | null) {
    const normalized = email?.trim().toLowerCase();
    return normalized ? normalized : null;
  }

  private generateJsonPhoneNumber(existingUsers: JsonUserRecord[]) {
    let phoneNumber = '';

    do {
      phoneNumber = `+25479${Math.floor(1000000 + Math.random() * 8999999)}`;
    } while (existingUsers.some((user) => user.phoneNumber === phoneNumber));

    return phoneNumber;
  }

  private async ensureJsonDb() {
    const dataDir = join(process.cwd(), 'data');
    const dbPath = this.getJsonDbPath();

    await fs.mkdir(dataDir, { recursive: true });

    try {
      await fs.access(dbPath);
    } catch {
      const now = new Date().toISOString();
      const seedUsers: JsonUserRecord[] = [
        {
          id: 'user-landlord-001',
          phoneNumber: this.configService.get<string>('SEED_LANDLORD_PHONE', '+254700000001'),
          email: this.normalizeEmail(
            this.configService.get<string>('SEED_LANDLORD_EMAIL', 'landlord@example.com'),
          ),
          firstName: 'Starmax',
          lastName: 'Landlord',
          passwordHash: null,
          role: RoleName.LANDLORD,
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
          phoneNumber: this.configService.get<string>('SEED_ADMIN_PHONE', '+254700000099'),
          email: this.normalizeEmail(
            this.configService.get<string>('SEED_ADMIN_EMAIL', 'admin@example.com'),
          ),
          firstName: 'Starmax',
          lastName: 'Admin',
          passwordHash: null,
          role: RoleName.ADMIN,
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
          phoneNumber: this.configService.get<string>('SEED_TENANT_PHONE', '+254700000010'),
          email: this.normalizeEmail(
            this.configService.get<string>('SEED_TENANT_EMAIL', 'tenant@example.com'),
          ),
          firstName: 'Starmax',
          lastName: 'Tenant',
          passwordHash: null,
          role: RoleName.TENANT,
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
          phoneNumber: this.configService.get<string>('SEED_CARETAKER_PHONE', '+254700000020'),
          email: this.normalizeEmail(
            this.configService.get<string>('SEED_CARETAKER_EMAIL', 'caretaker@example.com'),
          ),
          firstName: 'Starmax',
          lastName: 'Caretaker',
          passwordHash: null,
          role: RoleName.CARETAKER,
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

  private async readJsonDb(): Promise<JsonDb> {
    await this.ensureJsonDb();
    const raw = await fs.readFile(this.getJsonDbPath(), 'utf8');
    const parsed = JSON.parse(raw) as Partial<JsonDb>;

    return {
      users: parsed.users ?? [],
      companyInquiries: parsed.companyInquiries ?? [],
      demoAccessRequests: parsed.demoAccessRequests ?? [],
    };
  }

  private async writeJsonDb(db: JsonDb) {
    await fs.writeFile(this.getJsonDbPath(), JSON.stringify(db, null, 2), 'utf8');
  }

  private mapJsonUser(user: JsonUserRecord) {
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

  private async getRoleIdByName(roleName: RoleName) {
    const role = await this.prisma.role.findUnique({
      where: { name: roleName },
    });

    if (!role) {
      throw new NotFoundException(`Role ${roleName} was not found. Seed roles first.`);
    }

    return role.id;
  }

  private rethrowUniqueConstraint(error: unknown): never {
    if (
      error instanceof Prisma.PrismaClientKnownRequestError &&
      error.code === 'P2002'
    ) {
      const target = Array.isArray(error.meta?.target)
        ? error.meta.target.join(',')
        : String(error.meta?.target ?? '');

      if (target.includes('email')) {
        throw new ConflictException('An account with this email already exists');
      }

      if (target.includes('phoneNumber')) {
        throw new ConflictException('A user with this phone number already exists');
      }

      throw new ConflictException('A user with these details already exists');
    }

    throw error;
  }

  async create(dto: CreateUserDto) {
    if (this.isJsonDbMode()) {
      const db = await this.readJsonDb();

      if (db.users.some((user) => user.phoneNumber === dto.phoneNumber)) {
        throw new ConflictException('A user with this phone number already exists');
      }

      const normalizedEmail = this.normalizeEmail(dto.email);
      if (normalizedEmail && db.users.some((user) => user.email === normalizedEmail)) {
        throw new ConflictException('An account with this email already exists');
      }

      const phoneNumber = dto.phoneNumber ?? this.generateJsonPhoneNumber(db.users);
      const role = dto.role ?? RoleName.LANDLORD;
      const now = new Date().toISOString();
      const user: JsonUserRecord = {
        id: randomUUID(),
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
      throw new ConflictException('A user with this phone number already exists');
    }

    const normalizedEmail = this.normalizeEmail(dto.email);

    if (normalizedEmail) {
      const existingEmailUser = await this.prisma.user.findUnique({
        where: { email: normalizedEmail },
      });

      if (existingEmailUser) {
        throw new ConflictException('An account with this email already exists');
      }
    }

    const roleId = await this.getRoleIdByName(dto.role ?? RoleName.LANDLORD);
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
    } catch (error) {
      this.rethrowUniqueConstraint(error);
    }
  }

  async register(dto: RegisterUserDto) {
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

  async findOne(userId: string) {
    if (this.isJsonDbMode()) {
      const db = await this.readJsonDb();
      const user = db.users.find((item) => item.id === userId);

      if (!user) {
        throw new NotFoundException('User not found');
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
      throw new NotFoundException('User not found');
    }

    return this.toUserResponse(user);
  }

  async update(userId: string, dto: UpdateUserDto) {
    if (this.isJsonDbMode()) {
      const db = await this.readJsonDb();
      const userIndex = db.users.findIndex((item) => item.id === userId);

      if (userIndex === -1) {
        throw new NotFoundException('User not found');
      }

      const existing = db.users[userIndex];
      const normalizedEmail = dto.email !== undefined ? this.normalizeEmail(dto.email) : existing.email;

      if (
        dto.phoneNumber &&
        db.users.some((user) => user.id !== userId && user.phoneNumber === dto.phoneNumber)
      ) {
        throw new ConflictException('A user with this phone number already exists');
      }

      if (
        normalizedEmail &&
        db.users.some((user) => user.id !== userId && user.email === normalizedEmail)
      ) {
        throw new ConflictException('An account with this email already exists');
      }

      const updated: JsonUserRecord = {
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

    const data: {
      phoneNumber?: string;
      email?: string | null;
      firstName?: string;
      lastName?: string;
      isActive?: boolean;
      passwordHash?: string | null;
      roleId?: string;
    } = {};

    if (dto.phoneNumber !== undefined) data.phoneNumber = dto.phoneNumber;
    if (dto.email !== undefined) data.email = this.normalizeEmail(dto.email);
    if (dto.firstName !== undefined) data.firstName = dto.firstName;
    if (dto.lastName !== undefined) data.lastName = dto.lastName;
    if (dto.isActive !== undefined) data.isActive = dto.isActive;
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
    } catch (error) {
      this.rethrowUniqueConstraint(error);
      throw new BadRequestException('Unable to update user. Check the submitted values.');
    }
  }

  async remove(userId: string) {
    if (this.isJsonDbMode()) {
      const db = await this.readJsonDb();
      const before = db.users.length;
      db.users = db.users.filter((user) => user.id !== userId);

      if (db.users.length === before) {
        throw new NotFoundException('User not found');
      }

      await this.writeJsonDb(db);
      return { message: 'User deleted successfully' };
    }

    await this.findOne(userId);

    try {
      await this.prisma.user.delete({ where: { id: userId } });
      return { message: 'User deleted successfully' };
    } catch {
      throw new BadRequestException(
        'Unable to delete user. User may be referenced by related records.',
      );
    }
  }

  async assignRole(userId: string, dto: AssignRoleDto) {
    if (this.isJsonDbMode()) {
      const db = await this.readJsonDb();
      const userIndex = db.users.findIndex((item) => item.id === userId);

      if (userIndex === -1) {
        throw new NotFoundException('User not found');
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

  async getProfile(userId: string) {
    if (this.isJsonDbMode()) {
      const db = await this.readJsonDb();
      const user = db.users.find((item) => item.id === userId);

      if (!user) {
        throw new NotFoundException('User not found');
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
      throw new NotFoundException('User not found');
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
              imageUrls: (user.tenantProfile.unit.imageUrls as string[] | null) ?? [],
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

  async updateProfile(userId: string, dto: UpdateProfileDto) {
    if (this.isJsonDbMode()) {
      const db = await this.readJsonDb();
      const userIndex = db.users.findIndex((item) => item.id === userId);

      if (userIndex === -1) {
        throw new NotFoundException('User not found');
      }

      const existing = db.users[userIndex];
      const normalizedEmail = dto.email !== undefined ? this.normalizeEmail(dto.email) : existing.email;

      if (
        normalizedEmail &&
        db.users.some((user) => user.id !== userId && user.email === normalizedEmail)
      ) {
        throw new ConflictException('An account with this email already exists');
      }

      const updated: JsonUserRecord = {
        ...existing,
        phoneNumber: dto.phoneNumber ?? existing.phoneNumber,
        email: normalizedEmail,
        firstName: dto.firstName !== undefined ? dto.firstName : existing.firstName,
        lastName: dto.lastName !== undefined ? dto.lastName : existing.lastName,
        emergencyContactName:
          dto.emergencyContactName !== undefined
            ? dto.emergencyContactName
            : existing.emergencyContactName,
        emergencyContactPhone:
          dto.emergencyContactPhone !== undefined
            ? dto.emergencyContactPhone
            : existing.emergencyContactPhone,
        bio: dto.bio !== undefined ? dto.bio : existing.bio,
        profileImageUrl:
          dto.profileImageUrl !== undefined ? dto.profileImageUrl : existing.profileImageUrl,
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

    const data: Prisma.UserUpdateInput = {
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
    } catch (error) {
      this.rethrowUniqueConstraint(error);
      throw new BadRequestException('Unable to update profile. Check the submitted values.');
    }
  }

  async findByPhoneNumber(phoneNumber: string) {
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

  async findByEmail(email: string) {
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

  async findById(userId: string) {
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

  isAllowedAppRole(role: RoleName) {
    return (
      role === RoleName.LANDLORD ||
      role === RoleName.TENANT ||
      role === RoleName.ADMIN ||
      role === RoleName.CARETAKER
    );
  }

  async activateAndVerifyUser(userId: string) {
    if (this.isJsonDbMode()) {
      const db = await this.readJsonDb();
      const userIndex = db.users.findIndex((item) => item.id === userId);

      if (userIndex === -1) {
        throw new NotFoundException('User not found');
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

  async getDemoAccessExpiry(loginEmail: string) {
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

  async storeDemoAccessRequest(entry: {
    recipientEmail: string;
    loginEmail: string;
    expiresAt: Date;
    name?: string;
    userId?: string;
  }) {
    if (!this.isJsonDbMode()) {
      return;
    }

    const db = await this.readJsonDb();
    const record: DemoAccessRequestRecord = {
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

  async updatePassword(userId: string, hashedPassword: string) {
    if (this.isJsonDbMode()) {
      const db = await this.readJsonDb();
      const userIndex = db.users.findIndex((item) => item.id === userId);

      if (userIndex === -1) {
        throw new NotFoundException('User not found');
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
}
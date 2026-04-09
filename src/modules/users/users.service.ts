import {
  BadRequestException,
  ConflictException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { Prisma, RoleName } from '@prisma/client';
import * as bcrypt from 'bcryptjs';
import { PrismaService } from '../../prisma/prisma.service';
import { AssignRoleDto } from './dto/assign-role.dto';
import { CreateUserDto } from './dto/create-user.dto';
import { RegisterUserDto } from './dto/register-user.dto';
import { UpdateProfileDto } from './dto/update-profile.dto';
import { UpdateUserDto } from './dto/update-user.dto';

@Injectable()
export class UsersService {
  constructor(private readonly prisma: PrismaService) {}

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
      createdAt: user.createdAt,
      updatedAt: user.updatedAt,
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

  private normalizeEmail(email?: string | null) {
    const normalized = email?.trim().toLowerCase();
    return normalized ? normalized : null;
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
    const existingUser = await this.prisma.user.findUnique({
      where: { phoneNumber: dto.phoneNumber },
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
    } catch (error) {
      this.rethrowUniqueConstraint(error);
    }
  }

  async register(dto: RegisterUserDto) {
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

  async findOne(userId: string) {
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
    await this.findOne(userId);

    try {
      await this.prisma.user.delete({ where: { id: userId } });
      return { message: 'User deleted successfully' };
    } catch (error) {
      throw new BadRequestException(
        'Unable to delete user. User may be referenced by related records.',
      );
    }
  }

  async assignRole(userId: string, dto: AssignRoleDto) {
    const roleId = await this.getRoleIdByName(dto.role);

    const user = await this.prisma.user.update({
      where: { id: userId },
      data: { roleId },
      include: { role: true },
    });

    return this.toUserResponse(user);
  }

  async getProfile(userId: string) {
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
    return this.prisma.user.findUnique({
      where: { phoneNumber },
      include: { role: true },
    });
  }

  async findByEmail(email: string) {
    return this.prisma.user.findUnique({
      where: { email: email.trim().toLowerCase() },
      include: { role: true },
    });
  }

  async findById(userId: string) {
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

  async updatePassword(userId: string, hashedPassword: string) {
    return this.prisma.user.update({
      where: { id: userId },
      data: { passwordHash: hashedPassword },
    });
  }
}
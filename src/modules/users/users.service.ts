import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { RoleName } from '@prisma/client';
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

  async create(dto: CreateUserDto) {
    const existingUser = await this.prisma.user.findUnique({
      where: { phoneNumber: dto.phoneNumber },
    });

    if (existingUser) {
      throw new BadRequestException('User with this phone number already exists');
    }

    const roleId = await this.getRoleIdByName(dto.role);

    const passwordHash = dto.password ? await bcrypt.hash(dto.password, 10) : null;

    const user = await this.prisma.user.create({
      data: {
        phoneNumber: dto.phoneNumber,
        email: dto.email,
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
      email?: string;
      firstName?: string;
      lastName?: string;
      isActive?: boolean;
      passwordHash?: string | null;
      roleId?: string;
    } = {};

    if (dto.phoneNumber !== undefined) data.phoneNumber = dto.phoneNumber;
    if (dto.email !== undefined) data.email = dto.email;
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
      throw new BadRequestException('Unable to update user. Check unique constraints.');
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
    return this.findOne(userId);
  }

  async updateProfile(userId: string, dto: UpdateProfileDto) {
    const updateDto: UpdateUserDto = {
      phoneNumber: dto.phoneNumber,
      email: dto.email,
      firstName: dto.firstName,
      lastName: dto.lastName,
      password: dto.password,
    };

    return this.update(userId, updateDto);
  }

  async findByPhoneNumber(phoneNumber: string) {
    return this.prisma.user.findUnique({
      where: { phoneNumber },
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
      role === RoleName.LANDLORD || role === RoleName.TENANT || role === RoleName.ADMIN
    );
  }
}
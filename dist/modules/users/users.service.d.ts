import { RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { AssignRoleDto } from './dto/assign-role.dto';
import { CreateUserDto } from './dto/create-user.dto';
import { RegisterUserDto } from './dto/register-user.dto';
import { UpdateProfileDto } from './dto/update-profile.dto';
import { UpdateUserDto } from './dto/update-user.dto';
export declare class UsersService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    private toUserResponse;
    private getRoleIdByName;
    create(dto: CreateUserDto): Promise<{
        id: string;
        phoneNumber: string;
        email: string | null;
        firstName: string | null;
        lastName: string | null;
        role: import(".prisma/client").$Enums.RoleName;
        isActive: boolean;
        createdAt: Date;
        updatedAt: Date;
    }>;
    register(dto: RegisterUserDto): Promise<{
        id: string;
        phoneNumber: string;
        email: string | null;
        firstName: string | null;
        lastName: string | null;
        role: import(".prisma/client").$Enums.RoleName;
        isActive: boolean;
        createdAt: Date;
        updatedAt: Date;
    }>;
    findAll(): Promise<{
        id: string;
        phoneNumber: string;
        email: string | null;
        firstName: string | null;
        lastName: string | null;
        role: import(".prisma/client").$Enums.RoleName;
        isActive: boolean;
        createdAt: Date;
        updatedAt: Date;
    }[]>;
    findOne(userId: string): Promise<{
        id: string;
        phoneNumber: string;
        email: string | null;
        firstName: string | null;
        lastName: string | null;
        role: import(".prisma/client").$Enums.RoleName;
        isActive: boolean;
        createdAt: Date;
        updatedAt: Date;
    }>;
    update(userId: string, dto: UpdateUserDto): Promise<{
        id: string;
        phoneNumber: string;
        email: string | null;
        firstName: string | null;
        lastName: string | null;
        role: import(".prisma/client").$Enums.RoleName;
        isActive: boolean;
        createdAt: Date;
        updatedAt: Date;
    }>;
    remove(userId: string): Promise<{
        message: string;
    }>;
    assignRole(userId: string, dto: AssignRoleDto): Promise<{
        id: string;
        phoneNumber: string;
        email: string | null;
        firstName: string | null;
        lastName: string | null;
        role: import(".prisma/client").$Enums.RoleName;
        isActive: boolean;
        createdAt: Date;
        updatedAt: Date;
    }>;
    getProfile(userId: string): Promise<{
        id: string;
        phoneNumber: string;
        email: string | null;
        firstName: string | null;
        lastName: string | null;
        role: import(".prisma/client").$Enums.RoleName;
        isActive: boolean;
        createdAt: Date;
        updatedAt: Date;
    }>;
    updateProfile(userId: string, dto: UpdateProfileDto): Promise<{
        id: string;
        phoneNumber: string;
        email: string | null;
        firstName: string | null;
        lastName: string | null;
        role: import(".prisma/client").$Enums.RoleName;
        isActive: boolean;
        createdAt: Date;
        updatedAt: Date;
    }>;
    findByPhoneNumber(phoneNumber: string): Promise<({
        role: {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            name: import(".prisma/client").$Enums.RoleName;
            description: string | null;
        };
    } & {
        id: string;
        phoneNumber: string;
        createdAt: Date;
        updatedAt: Date;
        email: string | null;
        firstName: string | null;
        lastName: string | null;
        isActive: boolean;
        passwordHash: string | null;
        roleId: string;
    }) | null>;
    findById(userId: string): Promise<({
        role: {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            name: import(".prisma/client").$Enums.RoleName;
            description: string | null;
        };
    } & {
        id: string;
        phoneNumber: string;
        createdAt: Date;
        updatedAt: Date;
        email: string | null;
        firstName: string | null;
        lastName: string | null;
        isActive: boolean;
        passwordHash: string | null;
        roleId: string;
    }) | null>;
    isAllowedAppRole(role: RoleName): role is "LANDLORD" | "TENANT" | "ADMIN";
}

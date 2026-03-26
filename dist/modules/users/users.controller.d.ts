import { RoleName } from '@prisma/client';
import { AssignRoleDto } from './dto/assign-role.dto';
import { CreateUserDto } from './dto/create-user.dto';
import { UpdateProfileDto } from './dto/update-profile.dto';
import { UpdateUserDto } from './dto/update-user.dto';
import { UserIdParamDto } from './dto/user-id-param.dto';
import { UsersService } from './users.service';
type AuthenticatedRequest = {
    user: {
        userId: string;
        role: RoleName;
        phoneNumber: string;
    };
};
export declare class UsersController {
    private readonly usersService;
    constructor(usersService: UsersService);
    getMyProfile(req: AuthenticatedRequest): Promise<{
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
    updateMyProfile(req: AuthenticatedRequest, dto: UpdateProfileDto): Promise<{
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
    findOne(params: UserIdParamDto): Promise<{
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
    update(params: UserIdParamDto, dto: UpdateUserDto): Promise<{
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
    assignRole(params: UserIdParamDto, dto: AssignRoleDto): Promise<{
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
    remove(params: UserIdParamDto): Promise<{
        message: string;
    }>;
}
export {};

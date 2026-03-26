import { RoleName } from '@prisma/client';
export declare class UpdateUserDto {
    phoneNumber?: string;
    email?: string;
    password?: string;
    firstName?: string;
    lastName?: string;
    isActive?: boolean;
    role?: RoleName;
}

import { RoleName } from '@prisma/client';
export declare class RegisterUserDto {
    phoneNumber: string;
    email?: string;
    password?: string;
    firstName?: string;
    lastName?: string;
    role: RoleName;
}

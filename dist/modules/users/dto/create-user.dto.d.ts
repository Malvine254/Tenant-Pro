import { RoleName } from '@prisma/client';
export declare class CreateUserDto {
    phoneNumber: string;
    email?: string;
    password?: string;
    firstName?: string;
    lastName?: string;
    role: RoleName;
}

import { RegisterUserDto } from '../users/dto/register-user.dto';
import { UsersService } from '../users/users.service';
import { RequestOtpDto } from './dto/request-otp.dto';
import { VerifyOtpDto } from './dto/verify-otp.dto';
import { AuthService } from './auth.service';
export declare class AuthController {
    private readonly authService;
    private readonly usersService;
    constructor(authService: AuthService, usersService: UsersService);
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
    requestOtp(dto: RequestOtpDto): Promise<{
        message: string;
        phoneNumber: string;
        otpCode: string;
        expiresAt: string;
    }>;
    verifyOtp(dto: VerifyOtpDto): Promise<{
        accessToken: string;
        tokenType: string;
        user: {
            id: string;
            phoneNumber: string;
            email: string | null;
            firstName: string | null;
            lastName: string | null;
            role: import(".prisma/client").$Enums.RoleName;
        };
    }>;
    me(req: {
        user: unknown;
    }): unknown;
    adminOnly(): {
        message: string;
    };
}

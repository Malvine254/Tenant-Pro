import { RegisterUserDto } from '../users/dto/register-user.dto';
import { UsersService } from '../users/users.service';
import { LoginWithEmailDto } from './dto/login-with-email.dto';
import { RequestOtpDto } from './dto/request-otp.dto';
import { VerifyOtpDto } from './dto/verify-otp.dto';
import { RequestEmailOtpDto } from './dto/request-email-otp.dto';
import { RequestDemoAccessDto } from './dto/request-demo-access.dto';
import { VerifyEmailOtpDto } from './dto/verify-email-otp.dto';
import { ForgotPasswordDto } from './dto/forgot-password.dto';
import { ResetPasswordDto } from './dto/reset-password.dto';
import { AuthService } from './auth.service';
export declare class AuthController {
    private readonly authService;
    private readonly usersService;
    constructor(authService: AuthService, usersService: UsersService);
    register(dto: RegisterUserDto): Promise<{
        message: string;
        email: string;
        user: {
            id: string;
            phoneNumber: string;
            email: string | null;
            firstName: string | null;
            lastName: string | null;
            fullName: string;
            profileImageUrl: string | null;
            emergencyContactName: string | null;
            emergencyContactPhone: string | null;
            bio: string | null;
            role: import(".prisma/client").$Enums.RoleName;
            isActive: boolean;
            emailVerified: boolean;
            createdAt: Date;
            updatedAt: Date;
        };
    }>;
    requestDemoAccess(dto: RequestDemoAccessDto): Promise<{
        message: string;
        expiresAt: string;
    }>;
    loginWithEmail(dto: LoginWithEmailDto): Promise<{
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
    requestEmailOtp(dto: RequestEmailOtpDto): Promise<{
        message: string;
        email: string;
        expiresAt: string;
    }>;
    verifyEmailOtp(dto: VerifyEmailOtpDto): Promise<{
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
    forgotPassword(dto: ForgotPasswordDto): Promise<{
        message: string;
    }>;
    resetPassword(dto: ResetPasswordDto): Promise<{
        message: string;
    }>;
    me(req: {
        user: unknown;
    }): unknown;
    adminOnly(): {
        message: string;
    };
}

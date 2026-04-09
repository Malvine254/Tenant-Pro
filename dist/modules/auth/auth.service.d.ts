import { ConfigService } from '@nestjs/config';
import { JwtService } from '@nestjs/jwt';
import { UsersService } from '../users/users.service';
import { EmailService } from '../email/email.service';
import { OtpService } from './otp.service';
export declare class AuthService {
    private readonly usersService;
    private readonly otpService;
    private readonly emailService;
    private readonly jwtService;
    private readonly configService;
    constructor(usersService: UsersService, otpService: OtpService, emailService: EmailService, jwtService: JwtService, configService: ConfigService);
    private normalizeEmail;
    private getResetOtpKey;
    private verifyOtpWithFallback;
    loginWithEmailPassword(email: string, password: string): Promise<{
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
    requestLoginOtp(phoneNumber: string): Promise<{
        message: string;
        phoneNumber: string;
        otpCode: string;
        expiresAt: string;
    }>;
    verifyLoginOtp(phoneNumber: string, code: string): Promise<{
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
    requestEmailOtp(email: string): Promise<{
        message: string;
        email: string;
        expiresAt: string;
    }>;
    verifyEmailOtp(email: string, code: string): Promise<{
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
    forgotPassword(email: string): Promise<{
        message: string;
    }>;
    resetPassword(email: string, code: string, newPassword: string): Promise<{
        message: string;
    }>;
}

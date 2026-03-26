import { ConfigService } from '@nestjs/config';
import { JwtService } from '@nestjs/jwt';
import { UsersService } from '../users/users.service';
import { OtpService } from './otp.service';
export declare class AuthService {
    private readonly usersService;
    private readonly otpService;
    private readonly jwtService;
    private readonly configService;
    constructor(usersService: UsersService, otpService: OtpService, jwtService: JwtService, configService: ConfigService);
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
}

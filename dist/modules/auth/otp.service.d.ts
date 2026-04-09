import { ConfigService } from '@nestjs/config';
export declare class OtpService {
    private readonly configService;
    private readonly otpStore;
    private readonly ttlMs;
    private readonly otpLength;
    private readonly resendDelayMs;
    constructor(configService: ConfigService);
    generateOtp(phoneNumber: string): {
        code: string;
        expiresAt: number;
    };
    generateOtpForIdentifier(identifier: string): {
        code: string;
        expiresAt: number;
    };
    verifyOtp(phoneNumber: string, code: string): void;
    verifyOtpForIdentifier(identifier: string, code: string): void;
    getResendWaitTime(identifier: string): number;
}

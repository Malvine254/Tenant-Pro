export declare class OtpService {
    private readonly otpStore;
    private readonly ttlMs;
    generateOtp(phoneNumber: string): {
        code: string;
        expiresAt: number;
    };
    verifyOtp(phoneNumber: string, code: string): void;
}

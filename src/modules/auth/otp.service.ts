import { Injectable, UnauthorizedException } from '@nestjs/common';

type OtpRecord = {
  code: string;
  expiresAt: number;
};

@Injectable()
export class OtpService {
  private readonly otpStore = new Map<string, OtpRecord>();
  private readonly ttlMs = 5 * 60 * 1000;

  generateOtp(phoneNumber: string) {
    const code = Math.floor(100000 + Math.random() * 900000).toString();
    const expiresAt = Date.now() + this.ttlMs;

    this.otpStore.set(phoneNumber, { code, expiresAt });

    return {
      code,
      expiresAt,
    };
  }

  verifyOtp(phoneNumber: string, code: string) {
    const record = this.otpStore.get(phoneNumber);

    if (!record) {
      throw new UnauthorizedException('OTP not requested or expired');
    }

    if (Date.now() > record.expiresAt) {
      this.otpStore.delete(phoneNumber);
      throw new UnauthorizedException('OTP expired');
    }

    if (record.code !== code) {
      throw new UnauthorizedException('Invalid OTP code');
    }

    this.otpStore.delete(phoneNumber);
  }
}
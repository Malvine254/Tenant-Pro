import { Injectable, UnauthorizedException } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';

type OtpRecord = {
  code: string;
  expiresAt: number;
  lastSentAt?: number;
};

@Injectable()
export class OtpService {
  private readonly otpStore = new Map<string, OtpRecord>();
  private readonly ttlMs: number;
  private readonly otpLength: number;
  private readonly resendDelayMs: number;

  constructor(private readonly configService: ConfigService) {
    const expiryMinutes = parseInt(
      this.configService.get<string>('OTP_EXPIRY_MINUTES', '10'),
    );
    this.ttlMs = expiryMinutes * 60 * 1000;

    this.otpLength = parseInt(this.configService.get<string>('OTP_LENGTH', '6'));
    
    const resendDelay = parseInt(
      this.configService.get<string>('OTP_RESEND_DELAY_SECONDS', '60'),
    );
    this.resendDelayMs = resendDelay * 1000;
  }

  /**
   * Generate OTP for phone number (backward compatible)
   */
  generateOtp(phoneNumber: string) {
    return this.generateOtpForIdentifier(phoneNumber);
  }

  /**
   * Generate OTP for any identifier (email or phone)
   */
  generateOtpForIdentifier(identifier: string) {
    // Check if too soon to resend
    const existing = this.otpStore.get(identifier);
    if (existing?.lastSentAt) {
      const timeSinceLastSend = Date.now() - existing.lastSentAt;
      if (timeSinceLastSend < this.resendDelayMs) {
        const waitTime = Math.ceil((this.resendDelayMs - timeSinceLastSend) / 1000);
        throw new UnauthorizedException(
          `Please wait ${waitTime} seconds before requesting a new OTP`,
        );
      }
    }

    const max = Math.pow(10, this.otpLength) - 1;
    const min = Math.pow(10, this.otpLength - 1);
    const code = Math.floor(min + Math.random() * (max - min + 1)).toString();
    const expiresAt = Date.now() + this.ttlMs;
    const lastSentAt = Date.now();

    this.otpStore.set(identifier, { code, expiresAt, lastSentAt });

    return {
      code,
      expiresAt,
    };
  }

  /**
   * Verify OTP for phone number (backward compatible)
   */
  verifyOtp(phoneNumber: string, code: string) {
    return this.verifyOtpForIdentifier(phoneNumber, code);
  }

  /**
   * Verify OTP for any identifier (email or phone)
   */
  verifyOtpForIdentifier(identifier: string, code: string) {
    const record = this.otpStore.get(identifier);

    if (!record) {
      throw new UnauthorizedException('OTP not requested or expired');
    }

    if (Date.now() > record.expiresAt) {
      this.otpStore.delete(identifier);
      throw new UnauthorizedException('OTP expired');
    }

    if (record.code !== code) {
      throw new UnauthorizedException('Invalid OTP code');
    }

    this.otpStore.delete(identifier);
  }

  /**
   * Get remaining time before OTP can be resent
   */
  getResendWaitTime(identifier: string): number {
    const record = this.otpStore.get(identifier);
    if (!record?.lastSentAt) {
      return 0;
    }

    const timeSinceLastSend = Date.now() - record.lastSentAt;
    if (timeSinceLastSend >= this.resendDelayMs) {
      return 0;
    }

    return Math.ceil((this.resendDelayMs - timeSinceLastSend) / 1000);
  }
}
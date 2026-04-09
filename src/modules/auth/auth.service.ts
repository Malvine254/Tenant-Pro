import { BadRequestException, Injectable, UnauthorizedException } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { JwtService } from '@nestjs/jwt';
import { RoleName } from '@prisma/client';
import * as bcrypt from 'bcryptjs';
import { UsersService } from '../users/users.service';
import { EmailService } from '../email/email.service';
import { OtpService } from './otp.service';

@Injectable()
export class AuthService {
  constructor(
    private readonly usersService: UsersService,
    private readonly otpService: OtpService,
    private readonly emailService: EmailService,
    private readonly jwtService: JwtService,
    private readonly configService: ConfigService,
  ) {}

  private normalizeEmail(email: string) {
    return email.trim().toLowerCase();
  }

  private getResetOtpKey(email: string) {
    return `reset:${this.normalizeEmail(email)}`;
  }

  private verifyOtpWithFallback(identifiers: string[], code: string) {
    let lastError: unknown;

    for (const identifier of [...new Set(identifiers)]) {
      try {
        this.otpService.verifyOtpForIdentifier(identifier, code.trim());
        return;
      } catch (error) {
        lastError = error;
      }
    }

    throw lastError;
  }

  async loginWithEmailPassword(email: string, password: string) {
    const user = await this.usersService.findByEmail(email);

    if (!user || !user.passwordHash || !user.isActive) {
      throw new UnauthorizedException('Invalid email or password');
    }

    if (!this.usersService.isAllowedAppRole(user.role.name as RoleName)) {
      throw new BadRequestException('Role is not allowed for this app login flow');
    }

    const passwordMatches = await bcrypt.compare(password, user.passwordHash);
    if (!passwordMatches) {
      throw new UnauthorizedException('Invalid email or password');
    }

    const payload = {
      sub: user.id,
      phoneNumber: user.phoneNumber,
      role: user.role.name,
    };

    const expiresIn = this.configService.get<string>('JWT_EXPIRES_IN', '1d');
    const accessToken = await this.jwtService.signAsync(payload, {
      expiresIn: expiresIn as never,
    });

    return {
      accessToken,
      tokenType: 'Bearer',
      user: {
        id: user.id,
        phoneNumber: user.phoneNumber,
        email: user.email,
        firstName: user.firstName,
        lastName: user.lastName,
        role: user.role.name,
      },
    };
  }

  async requestLoginOtp(phoneNumber: string) {
    const user = await this.usersService.findByPhoneNumber(phoneNumber);

    if (!user) {
      throw new UnauthorizedException('User not found. Register first.');
    }

    if (!this.usersService.isAllowedAppRole(user.role.name as RoleName)) {
      throw new BadRequestException('Role is not allowed for this app login flow');
    }

    const otp = this.otpService.generateOtp(phoneNumber);

    return {
      message: 'OTP generated (simulation mode)',
      phoneNumber,
      otpCode: otp.code,
      expiresAt: new Date(otp.expiresAt).toISOString(),
    };
  }

  async verifyLoginOtp(phoneNumber: string, code: string) {
    this.otpService.verifyOtp(phoneNumber, code);

    const user = await this.usersService.findByPhoneNumber(phoneNumber);

    if (!user || !user.isActive) {
      throw new UnauthorizedException('User is inactive or not found');
    }

    if (!this.usersService.isAllowedAppRole(user.role.name as RoleName)) {
      throw new BadRequestException('Role is not allowed for this app login flow');
    }

    const payload = {
      sub: user.id,
      phoneNumber: user.phoneNumber,
      role: user.role.name,
    };

    const expiresIn = this.configService.get<string>('JWT_EXPIRES_IN', '1d');
    const accessToken = await this.jwtService.signAsync(payload, {
      expiresIn: expiresIn as never,
    });

    return {
      accessToken,
      tokenType: 'Bearer',
      user: {
        id: user.id,
        phoneNumber: user.phoneNumber,
        email: user.email,
        firstName: user.firstName,
        lastName: user.lastName,
        role: user.role.name,
      },
    };
  }

  async requestEmailOtp(email: string) {
    const normalizedEmail = this.normalizeEmail(email);
    const user = await this.usersService.findByEmail(normalizedEmail);

    if (!user) {
      throw new UnauthorizedException('User not found. Please register first.');
    }

    if (!user.isActive) {
      throw new UnauthorizedException('Account is inactive');
    }

    if (!this.usersService.isAllowedAppRole(user.role.name as RoleName)) {
      throw new BadRequestException('Role is not allowed for this app login flow');
    }

    const otp = this.otpService.generateOtpForIdentifier(normalizedEmail);

    await this.emailService.sendOtpEmail(normalizedEmail, otp.code, user.firstName || undefined);

    return {
      message: 'OTP sent to your email',
      email: normalizedEmail,
      expiresAt: new Date(otp.expiresAt).toISOString(),
    };
  }

  async verifyEmailOtp(email: string, code: string) {
    const normalizedEmail = this.normalizeEmail(email);
    this.verifyOtpWithFallback([normalizedEmail, email.trim(), email], code);

    const user = await this.usersService.findByEmail(normalizedEmail);

    if (!user || !user.isActive) {
      throw new UnauthorizedException('User is inactive or not found');
    }

    if (!this.usersService.isAllowedAppRole(user.role.name as RoleName)) {
      throw new BadRequestException('Role is not allowed for this app login flow');
    }

    const payload = {
      sub: user.id,
      phoneNumber: user.phoneNumber,
      role: user.role.name,
    };

    const expiresIn = this.configService.get<string>('JWT_EXPIRES_IN', '1d');
    const accessToken = await this.jwtService.signAsync(payload, {
      expiresIn: expiresIn as never,
    });

    return {
      accessToken,
      tokenType: 'Bearer',
      user: {
        id: user.id,
        phoneNumber: user.phoneNumber,
        email: user.email,
        firstName: user.firstName,
        lastName: user.lastName,
        role: user.role.name,
      },
    };
  }

  async forgotPassword(email: string) {
    const normalizedEmail = this.normalizeEmail(email);
    const user = await this.usersService.findByEmail(normalizedEmail);

    if (!user) {
      return {
        message: 'If the email exists, a password reset code has been sent',
      };
    }

    const otp = this.otpService.generateOtpForIdentifier(this.getResetOtpKey(normalizedEmail));

    // Send OTP code directly (for mobile app use)
    await this.emailService.sendOtpEmail(
      normalizedEmail,
      otp.code,
      user.firstName || undefined,
    );

    return {
      message: 'Password reset code sent to your email',
    };
  }

  async resetPassword(email: string, code: string, newPassword: string) {
    const normalizedEmail = this.normalizeEmail(email);

    this.verifyOtpWithFallback(
      [
        this.getResetOtpKey(normalizedEmail),
        `reset:${email.trim()}`,
        `reset:${email}`,
      ],
      code,
    );

    const user = await this.usersService.findByEmail(normalizedEmail);

    if (!user) {
      throw new UnauthorizedException('User not found');
    }

    const hashedPassword = await bcrypt.hash(newPassword, 10);

    await this.usersService.updatePassword(user.id, hashedPassword);

    return {
      message: 'Password reset successfully',
    };
  }
}
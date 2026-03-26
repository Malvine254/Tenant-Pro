import { BadRequestException, Injectable, UnauthorizedException } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { JwtService } from '@nestjs/jwt';
import { RoleName } from '@prisma/client';
import { UsersService } from '../users/users.service';
import { OtpService } from './otp.service';

@Injectable()
export class AuthService {
  constructor(
    private readonly usersService: UsersService,
    private readonly otpService: OtpService,
    private readonly jwtService: JwtService,
    private readonly configService: ConfigService,
  ) {}

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
}
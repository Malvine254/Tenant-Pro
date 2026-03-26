import { Body, Controller, Get, Post, Req, UseGuards } from '@nestjs/common';
import { RoleName } from '@prisma/client';
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { RegisterUserDto } from '../users/dto/register-user.dto';
import { UsersService } from '../users/users.service';
import { RequestOtpDto } from './dto/request-otp.dto';
import { VerifyOtpDto } from './dto/verify-otp.dto';
import { AuthService } from './auth.service';

@Controller('auth')
export class AuthController {
  constructor(
    private readonly authService: AuthService,
    private readonly usersService: UsersService,
  ) {}

  @Post('register')
  async register(@Body() dto: RegisterUserDto) {
    return this.usersService.register(dto);
  }

  @Post('otp/request')
  async requestOtp(@Body() dto: RequestOtpDto) {
    return this.authService.requestLoginOtp(dto.phoneNumber);
  }

  @Post('otp/verify')
  async verifyOtp(@Body() dto: VerifyOtpDto) {
    return this.authService.verifyLoginOtp(dto.phoneNumber, dto.code);
  }

  @Get('me')
  @UseGuards(JwtAuthGuard)
  me(@Req() req: { user: unknown }) {
    return req.user;
  }

  @Get('admin-only')
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles(RoleName.ADMIN)
  adminOnly() {
    return { message: 'Admin access granted' };
  }
}
import {
  Body,
  BadRequestException,
  Controller,
  Delete,
  Get,
  Param,
  Patch,
  Post,
  Req,
  UploadedFile,
  UseGuards,
  UseInterceptors,
} from '@nestjs/common';
import { FileInterceptor } from '@nestjs/platform-express';
import { IsUUID } from 'class-validator';
import { RoleName } from '@prisma/client';
import { existsSync, mkdirSync } from 'fs';
import { extname, join } from 'path';
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { AssignRoleDto } from './dto/assign-role.dto';
import { CreateUserDto } from './dto/create-user.dto';
import { SaveDeviceTokenDto } from './dto/save-device-token.dto';
import { UpdateProfileDto } from './dto/update-profile.dto';
import { UpdateUserDto } from './dto/update-user.dto';
import { UserIdParamDto } from './dto/user-id-param.dto';
import { UsersService } from './users.service';

const { diskStorage } = require('multer');

const profileUploadDir = join(process.cwd(), 'uploads', 'profile');
if (!existsSync(profileUploadDir)) {
  mkdirSync(profileUploadDir, { recursive: true });
}

class AssignUnitDto {
  @IsUUID()
  unitId!: string;
}

type AuthenticatedRequest = {
  user: {
    userId: string;
    role: RoleName;
    phoneNumber: string;
  };
};

@Controller('users')
@UseGuards(JwtAuthGuard, RolesGuard)
export class UsersController {
  constructor(private readonly usersService: UsersService) {}

  @Get('me/profile')
  @Roles(RoleName.LANDLORD, RoleName.TENANT, RoleName.ADMIN)
  getMyProfile(@Req() req: AuthenticatedRequest) {
    return this.usersService.getProfile(req.user.userId);
  }

  @Patch('me/profile')
  @Roles(RoleName.LANDLORD, RoleName.TENANT, RoleName.ADMIN)
  updateMyProfile(@Req() req: AuthenticatedRequest, @Body() dto: UpdateProfileDto) {
    return this.usersService.updateProfile(req.user.userId, dto);
  }

  @Post('me/profile-image')
  @Roles(RoleName.LANDLORD, RoleName.TENANT, RoleName.ADMIN)
  @UseInterceptors(
    FileInterceptor('file', {
      storage: diskStorage({
        destination: (_req: unknown, _file: unknown, cb: (error: Error | null, destination: string) => void) =>
          cb(null, profileUploadDir),
        filename: (
          _req: unknown,
          file: { originalname: string },
          cb: (error: Error | null, filename: string) => void,
        ) => {
          const safeBase = file.originalname.replace(/[^a-zA-Z0-9._-]/g, '_').replace(/\.[^.]+$/, '');
          const unique = `${Date.now()}-${Math.round(Math.random() * 1e9)}`;
          cb(null, `${safeBase || 'profile'}-${unique}${extname(file.originalname).toLowerCase()}`);
        },
      }),
      limits: { fileSize: 5 * 1024 * 1024 },
      fileFilter: (
        _req: unknown,
        file: { mimetype: string },
        cb: (error: Error | null, acceptFile: boolean) => void,
      ) => {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.mimetype)) {
          cb(new BadRequestException('Only JPG, PNG, or WebP profile images are supported') as unknown as Error, false);
          return;
        }
        cb(null, true);
      },
    }),
  )
  uploadMyProfileImage(
    @Req() req: AuthenticatedRequest,
    @UploadedFile()
    file?: { originalname: string; filename: string; size: number; mimetype: string },
  ) {
    if (!file) {
      throw new BadRequestException('No profile image uploaded');
    }

    const profileImageUrl = `/uploads/profile/${file.filename}`;
    return this.usersService.updateProfile(req.user.userId, { profileImageUrl });
  }

  @Post('device-token')
  @Roles(RoleName.LANDLORD, RoleName.TENANT, RoleName.ADMIN, RoleName.CARETAKER)
  saveDeviceToken(@Req() req: AuthenticatedRequest, @Body() body: SaveDeviceTokenDto) {
    return this.usersService.saveDeviceToken(req.user.userId, body.token);
  }

  @Post()
  @Roles(RoleName.ADMIN)
  create(@Body() dto: CreateUserDto) {
    return this.usersService.create(dto);
  }

  @Get()
  @Roles(RoleName.ADMIN, RoleName.LANDLORD, RoleName.CARETAKER)
  findAll() {
    return this.usersService.findAll();
  }

  @Get(':id')
  @Roles(RoleName.ADMIN, RoleName.LANDLORD, RoleName.CARETAKER)
  findOne(@Param() params: UserIdParamDto) {
    return this.usersService.findOne(params.id);
  }

  @Patch(':id')
  @Roles(RoleName.ADMIN)
  update(@Param() params: UserIdParamDto, @Body() dto: UpdateUserDto) {
    return this.usersService.update(params.id, dto);
  }

  @Patch(':id/role')
  @Roles(RoleName.ADMIN)
  assignRole(@Param() params: UserIdParamDto, @Body() dto: AssignRoleDto) {
    return this.usersService.assignRole(params.id, dto);
  }

  @Delete(':id')
  @Roles(RoleName.ADMIN)
  remove(@Param() params: UserIdParamDto) {
    return this.usersService.remove(params.id);
  }

  @Get(':id/units')
  @Roles(RoleName.ADMIN, RoleName.LANDLORD)
  getUserUnits(@Param() params: UserIdParamDto) {
    return this.usersService.getUserUnits(params.id);
  }

  @Post(':id/assign-unit')
  @Roles(RoleName.ADMIN, RoleName.LANDLORD)
  assignUnit(
    @Req() req: AuthenticatedRequest,
    @Param() params: UserIdParamDto,
    @Body() dto: AssignUnitDto,
  ) {
    return this.usersService.assignUnit(req.user.role, req.user.userId, params.id, dto.unitId);
  }

  @Delete(':id/units/:unitId')
  @Roles(RoleName.ADMIN, RoleName.LANDLORD)
  removeUnit(
    @Req() req: AuthenticatedRequest,
    @Param() params: UserIdParamDto & { unitId: string },
  ) {
    return this.usersService.removeUnit(req.user.role, req.user.userId, params.id, params.unitId);
  }
}

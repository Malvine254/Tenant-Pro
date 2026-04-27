import {
  Body,
  Controller,
  Delete,
  Get,
  Param,
  Patch,
  Post,
  Req,
  UseGuards,
} from '@nestjs/common';
import { RoleName } from '@prisma/client';
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { AssignRoleDto } from './dto/assign-role.dto';
import { CreateUserDto } from './dto/create-user.dto';
import { UpdateProfileDto } from './dto/update-profile.dto';
import { UpdateUserDto } from './dto/update-user.dto';
import { UserIdParamDto } from './dto/user-id-param.dto';
import { UsersService } from './users.service';

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
}
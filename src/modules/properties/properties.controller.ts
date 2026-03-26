import { Body, Controller, Get, Param, Post, Req, UseGuards } from '@nestjs/common';
import { RoleName } from '@prisma/client';
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { CreatePropertyDto } from './dto/create-property.dto';
import { CreateUnitDto } from './dto/create-unit.dto';
import { PropertyIdParamDto } from './dto/property-id-param.dto';
import { PropertiesService } from './properties.service';

type AuthenticatedRequest = {
  user: {
    userId: string;
    role: RoleName;
    phoneNumber: string;
  };
};

@Controller('properties')
@UseGuards(JwtAuthGuard, RolesGuard)
export class PropertiesController {
  constructor(private readonly propertiesService: PropertiesService) {}

  @Post()
  @Roles(RoleName.LANDLORD, RoleName.ADMIN)
  createProperty(@Req() req: AuthenticatedRequest, @Body() dto: CreatePropertyDto) {
    return this.propertiesService.createProperty(req.user.userId, req.user.role, dto);
  }

  @Post(':propertyId/units')
  @Roles(RoleName.LANDLORD, RoleName.ADMIN)
  addUnit(
    @Req() req: AuthenticatedRequest,
    @Param() params: PropertyIdParamDto,
    @Body() dto: CreateUnitDto,
  ) {
    return this.propertiesService.addUnit(req.user.userId, req.user.role, params.propertyId, dto);
  }

  @Get()
  @Roles(RoleName.LANDLORD, RoleName.ADMIN)
  listProperties(@Req() req: AuthenticatedRequest) {
    return this.propertiesService.listProperties(req.user.userId, req.user.role);
  }
}
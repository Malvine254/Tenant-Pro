import { Body, Controller, Param, Post, Req, UseGuards } from '@nestjs/common';
import { RoleName } from '@prisma/client';
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { CreateUnitDto } from '../properties/dto/create-unit.dto';
import { PropertyIdParamDto } from '../properties/dto/property-id-param.dto';
import { PropertiesService } from '../properties/properties.service';

type AuthenticatedRequest = {
  user: {
    userId: string;
    role: RoleName;
    phoneNumber: string;
  };
};

@Controller('units')
@UseGuards(JwtAuthGuard, RolesGuard)
@Roles(RoleName.LANDLORD)
export class UnitsController {
  constructor(private readonly propertiesService: PropertiesService) {}

  @Post('property/:propertyId')
  createUnitForProperty(
    @Req() req: AuthenticatedRequest,
    @Param() params: PropertyIdParamDto,
    @Body() dto: CreateUnitDto,
  ) {
    return this.propertiesService.addUnit(req.user.userId, params.propertyId, dto);
  }
}
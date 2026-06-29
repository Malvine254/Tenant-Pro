import {
  Body,
  Controller,
  Get,
  Param,
  Patch,
  Post,
  Req,
  UseGuards,
} from '@nestjs/common';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { Roles } from '../../common/decorators/roles.decorator';
import { RoleName } from '@prisma/client';
import { MaintenanceService } from './maintenance.service';
import { CreateRequestDto } from './dto/create-request.dto';
import { AssignCaretakerDto } from './dto/assign-caretaker.dto';
import { UpdateStatusDto } from './dto/update-status.dto';
import { RequestIdParamDto } from './dto/request-id-param.dto';

@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('maintenance')
export class MaintenanceController {
  constructor(private readonly maintenanceService: MaintenanceService) {}

  /**
   * POST /maintenance
   * Tenant creates a new maintenance request.
   */
  @Post()
  @Roles(RoleName.TENANT)
  createRequest(@Req() req: any, @Body() dto: CreateRequestDto) {
    return this.maintenanceService.createRequest(req.user.userId, dto);
  }

  /**
   * GET /maintenance
   * Role-aware list:
   *  TENANT     → own requests
   *  CARETAKER  → assigned requests
   *  LANDLORD   → requests for own properties
   *  ADMIN      → all requests
   */
  @Get()
  @Roles(RoleName.TENANT, RoleName.CARETAKER, RoleName.LANDLORD, RoleName.ADMIN)
  listRequests(@Req() req: any) {
    return this.maintenanceService.listRequests(req.user.userId, req.user.role);
  }

  /**
   * GET /maintenance/:id
   * Fetch a single request (access-gated per role).
   */
  @Get(':id')
  @Roles(RoleName.TENANT, RoleName.CARETAKER, RoleName.LANDLORD, RoleName.ADMIN)
  getRequest(@Req() req: any, @Param() params: RequestIdParamDto) {
    return this.maintenanceService.getRequest(req.user.userId, req.user.role, params.id);
  }

  /**
   * PATCH /maintenance/:id/assign
   * Landlord assigns a caretaker to a request.
   */
  @Patch(':id/assign')
  @Roles(RoleName.LANDLORD)
  assignCaretaker(
    @Req() req: any,
    @Param() params: RequestIdParamDto,
    @Body() dto: AssignCaretakerDto,
  ) {
    return this.maintenanceService.assignCaretaker(req.user.userId, params.id, dto);
  }

  /**
   * PATCH /maintenance/:id/status
   * Update the request status.
   * OPEN → IN_PROGRESS  (LANDLORD | CARETAKER | ADMIN)
   * IN_PROGRESS → RESOLVED  (LANDLORD | CARETAKER | ADMIN — CARETAKER must be assignee)
   * RESOLVED → CLOSED  (LANDLORD | ADMIN)
   * Any non-terminal → CLOSED  (LANDLORD | ADMIN)
   */
  @Patch(':id/status')
  @Roles(RoleName.LANDLORD, RoleName.CARETAKER, RoleName.ADMIN)
  updateStatus(
    @Req() req: any,
    @Param() params: RequestIdParamDto,
    @Body() dto: UpdateStatusDto,
  ) {
    return this.maintenanceService.updateStatus(req.user.userId, req.user.role, params.id, dto);
  }
}

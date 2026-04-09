import { Controller, Get, Param, Patch, Post, Req, UseGuards } from '@nestjs/common';
import { RoleName } from '@prisma/client';
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { NotificationsService } from './notifications.service';

type AuthenticatedRequest = {
  user: {
    userId: string;
    role: RoleName;
    phoneNumber: string;
  };
};

@Controller('notifications')
@UseGuards(JwtAuthGuard, RolesGuard)
export class NotificationsController {
  constructor(private readonly notificationsService: NotificationsService) {}

  @Get()
  @Roles(RoleName.TENANT, RoleName.LANDLORD, RoleName.ADMIN, RoleName.CARETAKER)
  list(@Req() req: AuthenticatedRequest) {
    return this.notificationsService.listForUser(req.user.userId);
  }

  @Patch(':id/read')
  @Roles(RoleName.TENANT, RoleName.LANDLORD, RoleName.ADMIN, RoleName.CARETAKER)
  markRead(@Req() req: AuthenticatedRequest, @Param('id') id: string) {
    return this.notificationsService.markRead(req.user.userId, id);
  }

  @Post('mark-all-read')
  @Roles(RoleName.TENANT, RoleName.LANDLORD, RoleName.ADMIN, RoleName.CARETAKER)
  markAllRead(@Req() req: AuthenticatedRequest) {
    return this.notificationsService.markAllRead(req.user.userId);
  }
}

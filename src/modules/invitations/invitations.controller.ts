import { Body, Controller, Post, Req, UseGuards } from '@nestjs/common';
import { RoleName } from '@prisma/client';
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { AcceptInvitationDto } from './dto/accept-invitation.dto';
import { CreateInvitationDto } from './dto/create-invitation.dto';
import { InvitationsService } from './invitations.service';

type AuthenticatedRequest = {
  user: {
    userId: string;
    role: RoleName;
    phoneNumber: string;
  };
};

@Controller('invitations')
@UseGuards(JwtAuthGuard, RolesGuard)
export class InvitationsController {
  constructor(private readonly invitationsService: InvitationsService) {}

  @Post()
  @Roles(RoleName.LANDLORD, RoleName.ADMIN)
  create(@Req() req: AuthenticatedRequest, @Body() dto: CreateInvitationDto) {
    return this.invitationsService.createInvitation(req.user.userId, req.user.role, dto);
  }

  @Post('accept')
  @Roles(RoleName.TENANT)
  accept(@Req() req: AuthenticatedRequest, @Body() dto: AcceptInvitationDto) {
    return this.invitationsService.acceptInvitation(req.user.userId, dto);
  }

  @Post('expire')
  @Roles(RoleName.LANDLORD, RoleName.ADMIN)
  expire(@Req() req: AuthenticatedRequest) {
    return this.invitationsService.expirePendingInvitations(req.user.userId, req.user.role);
  }
}
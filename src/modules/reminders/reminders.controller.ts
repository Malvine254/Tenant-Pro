import { Controller, Post, Param, Body, UseGuards } from '@nestjs/common';
import { RoleName } from '@prisma/client';
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { RemindersService } from './reminders.service';

@Controller('reminders')
@UseGuards(JwtAuthGuard, RolesGuard)
export class RemindersController {
  constructor(private readonly remindersService: RemindersService) {}

  @Post('rent/:invoiceId')
  @Roles(RoleName.LANDLORD, RoleName.ADMIN)
  async sendRentReminder(@Param('invoiceId') invoiceId: string) {
    return this.remindersService.sendManualRentReminder(invoiceId);
  }

  @Post('maintenance/:requestId')
  @Roles(RoleName.LANDLORD, RoleName.ADMIN, RoleName.CARETAKER)
  async sendMaintenanceUpdate(
    @Param('requestId') requestId: string,
    @Body() body: { status: string; message: string },
  ) {
    return this.remindersService.sendMaintenanceUpdate(
      requestId,
      body.status,
      body.message,
    );
  }

  @Post('welcome/:userId')
  @Roles(RoleName.ADMIN)
  async sendWelcomeEmail(@Param('userId') userId: string) {
    return this.remindersService.sendWelcomeEmail(userId);
  }
}

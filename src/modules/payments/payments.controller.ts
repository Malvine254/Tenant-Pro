import { Body, Controller, Get, Param, Post, Req, UseGuards } from '@nestjs/common';
import { RoleName } from '@prisma/client';
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { MpesaCallbackDto } from './dto/mpesa-callback.dto';
import { InitiatePaymentDto } from './dto/initiate-payment.dto';
import { PaymentsService } from './payments.service';

type AuthenticatedRequest = {
  user: {
    userId: string;
    role: RoleName;
    phoneNumber: string;
  };
};

@Controller('payments')
export class PaymentsController {
  constructor(private readonly paymentsService: PaymentsService) {}

  /**
   * POST /api/payments/pay
   * Triggers an STK Push. Accessible by TENANT (pays own invoice) or LANDLORD/ADMIN.
   */
  @Post('pay')
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles(RoleName.TENANT, RoleName.LANDLORD, RoleName.ADMIN)
  initiatePayment(@Req() req: AuthenticatedRequest, @Body() dto: InitiatePaymentDto) {
    return this.paymentsService.initiateStkPush(req.user.userId, req.user.role, dto);
  }

  /**
   * POST /api/payments/mpesa/callback
   * Public endpoint – receives M-Pesa Daraja STK Push result callbacks.
   * Must NOT be protected by JWT.
   */
  @Post('mpesa/callback')
  mpesaCallback(@Body() body: MpesaCallbackDto) {
    return this.paymentsService.handleCallback(body);
  }

  /**
   * GET /api/payments/invoice/:invoiceId
   * Fetch payment records (with transactions) for a given invoice.
   */
  @Get('invoice/:invoiceId')
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles(RoleName.TENANT, RoleName.LANDLORD, RoleName.ADMIN)
  getPaymentsByInvoice(
    @Req() req: AuthenticatedRequest,
    @Param('invoiceId') invoiceId: string,
  ) {
    return this.paymentsService.getPaymentsByInvoice(
      req.user.userId,
      req.user.role,
      invoiceId,
    );
  }
}

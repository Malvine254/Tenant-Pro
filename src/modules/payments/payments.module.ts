import { Module } from '@nestjs/common';
import { PaymentsController } from './payments.controller';
import { PaymentsService } from './payments.service';
import { MpesaService } from './mpesa.service';

@Module({
  controllers: [PaymentsController],
  providers: [PaymentsService, MpesaService],
})
export class PaymentsModule {}

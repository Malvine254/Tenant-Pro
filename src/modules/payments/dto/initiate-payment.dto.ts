import { Type } from 'class-transformer';
import { IsNumber, IsOptional, IsPositive, IsString, IsUUID, Matches } from 'class-validator';

export class InitiatePaymentDto {
  /** The invoice to pay */
  @IsUUID()
  invoiceId!: string;

  /** Phone number to receive STK Push – must be Safaricom format */
  @IsString()
  @Matches(/^(?:254|\+254|0)[17]\d{8}$/, {
    message: 'phoneNumber must be a valid Safaricom number (e.g. 2547XXXXXXXX)',
  })
  phoneNumber!: string;

  /**
   * Optional amount for partial payment.
   * If omitted the remaining balance (totalAmount - paidAmount) is charged.
   * Must be > 0 and <= remaining balance.
   */
  @IsOptional()
  @Type(() => Number)
  @IsNumber({ maxDecimalPlaces: 2 })
  @IsPositive()
  amount?: number;
}

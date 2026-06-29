import { BillingType } from '@prisma/client';
import { Type } from 'class-transformer';
import { IsDateString, IsEnum, IsInt, IsNumber, IsPositive, IsUUID, Max, Min } from 'class-validator';

export class CreateBillDto {
  @IsUUID()
  tenantId!: string;

  @IsEnum(BillingType)
  billingType!: BillingType;

  @Type(() => Number)
  @IsNumber({ maxDecimalPlaces: 2 })
  @IsPositive()
  amount!: number;

  @IsDateString()
  dueDate!: string;

  @Type(() => Number)
  @IsInt()
  @Min(1)
  @Max(12)
  periodMonth!: number;

  @Type(() => Number)
  @IsInt()
  @Min(2020)
  @Max(2100)
  periodYear!: number;
}

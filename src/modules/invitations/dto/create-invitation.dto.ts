import { Type } from 'class-transformer';
import {
  IsEmail,
  IsInt,
  IsOptional,
  IsString,
  IsUUID,
  Matches,
  Max,
  Min,
} from 'class-validator';

export class CreateInvitationDto {
  @IsUUID()
  propertyId!: string;

  @IsUUID()
  unitId!: string;

  @IsString()
  @Matches(/^\+?[1-9]\d{7,14}$/)
  phoneNumber!: string;

  @IsOptional()
  @IsEmail()
  tenantEmail?: string;

  @IsOptional()
  @IsString()
  tenantName?: string;

  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(1)
  @Max(168)
  expiresInHours?: number;

  @IsOptional()
  @IsString()
  sentVia?: string;
}
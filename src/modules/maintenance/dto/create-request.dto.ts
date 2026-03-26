import { IsEnum, IsOptional, IsString, MaxLength, MinLength } from 'class-validator';
import { MaintenancePriority } from '@prisma/client';

export class CreateRequestDto {
  @IsString()
  @MinLength(5)
  @MaxLength(120)
  title!: string;

  @IsString()
  @MinLength(10)
  @MaxLength(1000)
  description!: string;

  @IsOptional()
  @IsEnum(MaintenancePriority)
  priority?: MaintenancePriority;
}

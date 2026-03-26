import { Type } from 'class-transformer';
import { IsNumber, IsOptional, IsPositive, IsString } from 'class-validator';

export class CreateUnitDto {
  @IsString()
  unitNumber!: string;

  @IsOptional()
  @IsString()
  floor?: string;

  @Type(() => Number)
  @IsNumber({ maxDecimalPlaces: 2 })
  @IsPositive()
  rentAmount!: number;
}
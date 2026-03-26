import { IsOptional, IsString } from 'class-validator';

export class CreatePropertyDto {
  @IsString()
  name!: string;

  @IsOptional()
  @IsString()
  description?: string;

  @IsString()
  addressLine!: string;

  @IsString()
  city!: string;

  @IsOptional()
  @IsString()
  state?: string;

  @IsString()
  country!: string;
}
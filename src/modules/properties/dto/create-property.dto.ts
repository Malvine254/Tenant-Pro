import { IsOptional, IsString, IsUUID } from 'class-validator';

export class CreatePropertyDto {
  @IsOptional()
  @IsUUID()
  landlordId?: string;

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
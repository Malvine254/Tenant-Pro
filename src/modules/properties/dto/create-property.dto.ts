import { IsOptional, IsString, IsUUID, IsUrl } from 'class-validator';

export class CreatePropertyDto {
  @IsOptional()
  @IsUUID()
  landlordId?: string;

  @IsOptional()
  @IsUrl({ require_tld: false })
  coverImageUrl?: string;

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
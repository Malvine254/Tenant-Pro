import { IsOptional, IsString, MaxLength } from 'class-validator';

export class SendSupportMessageDto {
  @IsOptional()
  @IsString()
  @MaxLength(80)
  topic?: string;

  @IsOptional()
  @IsString()
  @MaxLength(120)
  subject?: string;

  @IsOptional()
  @IsString()
  tenantUserId?: string;

  @IsOptional()
  @IsString()
  @MaxLength(2000)
  text?: string;

  @IsOptional()
  @IsString()
  attachmentUri?: string;

  @IsOptional()
  @IsString()
  attachmentName?: string;
}

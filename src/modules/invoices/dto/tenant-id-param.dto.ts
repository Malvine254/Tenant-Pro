import { IsUUID } from 'class-validator';

export class TenantIdParamDto {
  @IsUUID()
  tenantId!: string;
}

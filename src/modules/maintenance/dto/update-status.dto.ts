import { IsEnum } from 'class-validator';
import { MaintenanceStatus } from '@prisma/client';

export class UpdateStatusDto {
  @IsEnum(MaintenanceStatus)
  status!: MaintenanceStatus;
}

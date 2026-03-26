import { RoleName } from '@prisma/client';
import { IsEnum } from 'class-validator';

export class AssignRoleDto {
  @IsEnum(RoleName)
  role!: RoleName;
}
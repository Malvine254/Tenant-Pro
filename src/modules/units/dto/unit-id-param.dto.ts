import { IsUUID } from 'class-validator';

export class UnitIdParamDto {
  @IsUUID()
  unitId!: string;
}

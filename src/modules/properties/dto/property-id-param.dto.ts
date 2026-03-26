import { IsUUID } from 'class-validator';

export class PropertyIdParamDto {
  @IsUUID()
  propertyId!: string;
}
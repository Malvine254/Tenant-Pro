import { IsUUID } from 'class-validator';

export class RequestIdParamDto {
  @IsUUID()
  id!: string;
}

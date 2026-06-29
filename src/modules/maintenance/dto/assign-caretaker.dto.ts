import { IsUUID } from 'class-validator';

export class AssignCaretakerDto {
  /** User ID of the caretaker to assign */
  @IsUUID()
  caretakerId!: string;
}

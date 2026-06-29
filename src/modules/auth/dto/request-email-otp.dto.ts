import { IsEmail, IsNotEmpty } from 'class-validator';

export class RequestEmailOtpDto {
  @IsEmail()
  @IsNotEmpty()
  email!: string;
}

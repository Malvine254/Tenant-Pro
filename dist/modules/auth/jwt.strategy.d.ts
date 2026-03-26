import { ConfigService } from '@nestjs/config';
import { RoleName } from '@prisma/client';
import { Strategy } from 'passport-jwt';
import { UsersService } from '../users/users.service';
type JwtPayload = {
    sub: string;
    phoneNumber: string;
    role: RoleName;
};
declare const JwtStrategy_base: new (...args: [opt: import("passport-jwt").StrategyOptionsWithRequest] | [opt: import("passport-jwt").StrategyOptionsWithoutRequest]) => Strategy & {
    validate(...args: any[]): unknown;
};
export declare class JwtStrategy extends JwtStrategy_base {
    private readonly usersService;
    constructor(configService: ConfigService, usersService: UsersService);
    validate(payload: JwtPayload): Promise<{
        userId: string;
        phoneNumber: string;
        role: import(".prisma/client").$Enums.RoleName;
    }>;
}
export {};

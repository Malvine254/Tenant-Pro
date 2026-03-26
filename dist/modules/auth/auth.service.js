"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.AuthService = void 0;
const common_1 = require("@nestjs/common");
const config_1 = require("@nestjs/config");
const jwt_1 = require("@nestjs/jwt");
const users_service_1 = require("../users/users.service");
const otp_service_1 = require("./otp.service");
let AuthService = class AuthService {
    constructor(usersService, otpService, jwtService, configService) {
        this.usersService = usersService;
        this.otpService = otpService;
        this.jwtService = jwtService;
        this.configService = configService;
    }
    async requestLoginOtp(phoneNumber) {
        const user = await this.usersService.findByPhoneNumber(phoneNumber);
        if (!user) {
            throw new common_1.UnauthorizedException('User not found. Register first.');
        }
        if (!this.usersService.isAllowedAppRole(user.role.name)) {
            throw new common_1.BadRequestException('Role is not allowed for this app login flow');
        }
        const otp = this.otpService.generateOtp(phoneNumber);
        return {
            message: 'OTP generated (simulation mode)',
            phoneNumber,
            otpCode: otp.code,
            expiresAt: new Date(otp.expiresAt).toISOString(),
        };
    }
    async verifyLoginOtp(phoneNumber, code) {
        this.otpService.verifyOtp(phoneNumber, code);
        const user = await this.usersService.findByPhoneNumber(phoneNumber);
        if (!user || !user.isActive) {
            throw new common_1.UnauthorizedException('User is inactive or not found');
        }
        if (!this.usersService.isAllowedAppRole(user.role.name)) {
            throw new common_1.BadRequestException('Role is not allowed for this app login flow');
        }
        const payload = {
            sub: user.id,
            phoneNumber: user.phoneNumber,
            role: user.role.name,
        };
        const expiresIn = this.configService.get('JWT_EXPIRES_IN', '1d');
        const accessToken = await this.jwtService.signAsync(payload, {
            expiresIn: expiresIn,
        });
        return {
            accessToken,
            tokenType: 'Bearer',
            user: {
                id: user.id,
                phoneNumber: user.phoneNumber,
                email: user.email,
                firstName: user.firstName,
                lastName: user.lastName,
                role: user.role.name,
            },
        };
    }
};
exports.AuthService = AuthService;
exports.AuthService = AuthService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [users_service_1.UsersService,
        otp_service_1.OtpService,
        jwt_1.JwtService,
        config_1.ConfigService])
], AuthService);
//# sourceMappingURL=auth.service.js.map
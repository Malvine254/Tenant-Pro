"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.AuthService = void 0;
const common_1 = require("@nestjs/common");
const config_1 = require("@nestjs/config");
const jwt_1 = require("@nestjs/jwt");
const client_1 = require("@prisma/client");
const bcrypt = __importStar(require("bcryptjs"));
const users_service_1 = require("../users/users.service");
const email_service_1 = require("../email/email.service");
const otp_service_1 = require("./otp.service");
let AuthService = class AuthService {
    constructor(usersService, otpService, emailService, jwtService, configService) {
        this.usersService = usersService;
        this.otpService = otpService;
        this.emailService = emailService;
        this.jwtService = jwtService;
        this.configService = configService;
        this.demoAccessStore = new Map();
    }
    normalizeEmail(email) {
        return email.trim().toLowerCase();
    }
    getResetOtpKey(email) {
        return `reset:${this.normalizeEmail(email)}`;
    }
    isDemoEmail(email) {
        return !!email && this.normalizeEmail(email).endsWith('@starmax.preview');
    }
    generateDemoPassword() {
        const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        const randomChunk = Array.from({ length: 8 }, () => alphabet[Math.floor(Math.random() * alphabet.length)]).join('');
        return `Starmax!${randomChunk}`;
    }
    generateDemoLoginEmail() {
        const token = `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 7)}`;
        return `demo+${token}@starmax.preview`;
    }
    verifyOtpWithFallback(identifiers, code) {
        let lastError;
        for (const identifier of [...new Set(identifiers)]) {
            try {
                this.otpService.verifyOtpForIdentifier(identifier, code.trim());
                return;
            }
            catch (error) {
                lastError = error;
            }
        }
        throw lastError;
    }
    async registerWithEmailVerification(dto) {
        const normalizedEmail = this.normalizeEmail(dto.email);
        const user = await this.usersService.register({
            ...dto,
            email: normalizedEmail,
            role: dto.role ?? client_1.RoleName.LANDLORD,
        });
        await this.requestEmailOtp(normalizedEmail);
        return {
            message: 'Account created. A verification code has been sent to your email.',
            email: normalizedEmail,
            user,
        };
    }
    async loginWithEmailPassword(email, password) {
        const normalizedEmail = this.normalizeEmail(email);
        const user = await this.usersService.findByEmail(normalizedEmail);
        const metadata = user;
        if (!user || !user.passwordHash || !user.isActive) {
            throw new common_1.UnauthorizedException('Invalid email or password');
        }
        if (metadata.emailVerified === false) {
            throw new common_1.UnauthorizedException('Please verify your email before signing in');
        }
        if (!this.usersService.isAllowedAppRole(user.role.name)) {
            throw new common_1.BadRequestException('Role is not allowed for this app login flow');
        }
        if (this.isDemoEmail(user.email)) {
            const expiresAt = this.demoAccessStore.get(normalizedEmail) ??
                (await this.usersService.getDemoAccessExpiry(normalizedEmail));
            if (expiresAt) {
                this.demoAccessStore.set(normalizedEmail, expiresAt);
            }
            if (!expiresAt || expiresAt < Date.now()) {
                throw new common_1.UnauthorizedException('Demo credentials expired. Please request a new demo login.');
            }
        }
        const passwordMatches = await bcrypt.compare(password, user.passwordHash);
        if (!passwordMatches) {
            throw new common_1.UnauthorizedException('Invalid email or password');
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
    async requestEmailOtp(email) {
        const normalizedEmail = this.normalizeEmail(email);
        const user = await this.usersService.findByEmail(normalizedEmail);
        if (!user) {
            throw new common_1.UnauthorizedException('User not found. Please register first.');
        }
        if (!this.usersService.isAllowedAppRole(user.role.name)) {
            throw new common_1.BadRequestException('Role is not allowed for this app login flow');
        }
        const otp = this.otpService.generateOtpForIdentifier(normalizedEmail);
        await this.emailService.sendVerificationEmail(normalizedEmail, otp.code, user.firstName || undefined);
        return {
            message: 'OTP sent to your email',
            email: normalizedEmail,
            expiresAt: new Date(otp.expiresAt).toISOString(),
        };
    }
    async verifyEmailOtp(email, code) {
        const normalizedEmail = this.normalizeEmail(email);
        this.verifyOtpWithFallback([normalizedEmail, email.trim(), email], code);
        const user = await this.usersService.findByEmail(normalizedEmail);
        if (!user) {
            throw new common_1.UnauthorizedException('User not found');
        }
        if (!this.usersService.isAllowedAppRole(user.role.name)) {
            throw new common_1.BadRequestException('Role is not allowed for this app login flow');
        }
        await this.usersService.activateAndVerifyUser(user.id);
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
    async requestDemoAccess(email, name) {
        const recipientEmail = this.normalizeEmail(email);
        const loginEmail = this.generateDemoLoginEmail();
        const temporaryPassword = this.generateDemoPassword();
        const expiresAt = new Date(Date.now() + 60 * 60 * 1000);
        const demoUser = await this.usersService.create({
            email: loginEmail,
            password: temporaryPassword,
            firstName: name?.trim() || 'Demo',
            lastName: 'Guest',
            role: client_1.RoleName.ADMIN,
        });
        await this.usersService.activateAndVerifyUser(demoUser.id);
        this.demoAccessStore.set(loginEmail, expiresAt.getTime());
        await this.usersService.storeDemoAccessRequest({
            recipientEmail,
            loginEmail,
            expiresAt,
            name: name?.trim(),
            userId: demoUser.id,
        });
        await this.emailService.sendDemoAccessEmail(recipientEmail, loginEmail, temporaryPassword, expiresAt, name?.trim() || 'there');
        return {
            message: 'Demo credentials sent to your email. They are valid for 1 hour.',
            expiresAt: expiresAt.toISOString(),
        };
    }
    async forgotPassword(email) {
        const normalizedEmail = this.normalizeEmail(email);
        const user = await this.usersService.findByEmail(normalizedEmail);
        if (!user) {
            return {
                message: 'If the email exists, a password reset code has been sent',
            };
        }
        const otp = this.otpService.generateOtpForIdentifier(this.getResetOtpKey(normalizedEmail));
        await this.emailService.sendOtpEmail(normalizedEmail, otp.code, user.firstName || undefined);
        return {
            message: 'Password reset code sent to your email',
        };
    }
    async resetPassword(email, code, newPassword) {
        const normalizedEmail = this.normalizeEmail(email);
        this.verifyOtpWithFallback([
            this.getResetOtpKey(normalizedEmail),
            `reset:${email.trim()}`,
            `reset:${email}`,
        ], code);
        const user = await this.usersService.findByEmail(normalizedEmail);
        if (!user) {
            throw new common_1.UnauthorizedException('User not found');
        }
        const hashedPassword = await bcrypt.hash(newPassword, 10);
        await this.usersService.updatePassword(user.id, hashedPassword);
        return {
            message: 'Password reset successfully',
        };
    }
};
exports.AuthService = AuthService;
exports.AuthService = AuthService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [users_service_1.UsersService,
        otp_service_1.OtpService,
        email_service_1.EmailService,
        jwt_1.JwtService,
        config_1.ConfigService])
], AuthService);
//# sourceMappingURL=auth.service.js.map
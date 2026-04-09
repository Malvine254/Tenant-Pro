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
exports.OtpService = void 0;
const common_1 = require("@nestjs/common");
const config_1 = require("@nestjs/config");
let OtpService = class OtpService {
    constructor(configService) {
        this.configService = configService;
        this.otpStore = new Map();
        const expiryMinutes = parseInt(this.configService.get('OTP_EXPIRY_MINUTES', '10'));
        this.ttlMs = expiryMinutes * 60 * 1000;
        this.otpLength = parseInt(this.configService.get('OTP_LENGTH', '6'));
        const resendDelay = parseInt(this.configService.get('OTP_RESEND_DELAY_SECONDS', '60'));
        this.resendDelayMs = resendDelay * 1000;
    }
    generateOtp(phoneNumber) {
        return this.generateOtpForIdentifier(phoneNumber);
    }
    generateOtpForIdentifier(identifier) {
        const existing = this.otpStore.get(identifier);
        if (existing?.lastSentAt) {
            const timeSinceLastSend = Date.now() - existing.lastSentAt;
            if (timeSinceLastSend < this.resendDelayMs) {
                const waitTime = Math.ceil((this.resendDelayMs - timeSinceLastSend) / 1000);
                throw new common_1.UnauthorizedException(`Please wait ${waitTime} seconds before requesting a new OTP`);
            }
        }
        const max = Math.pow(10, this.otpLength) - 1;
        const min = Math.pow(10, this.otpLength - 1);
        const code = Math.floor(min + Math.random() * (max - min + 1)).toString();
        const expiresAt = Date.now() + this.ttlMs;
        const lastSentAt = Date.now();
        this.otpStore.set(identifier, { code, expiresAt, lastSentAt });
        return {
            code,
            expiresAt,
        };
    }
    verifyOtp(phoneNumber, code) {
        return this.verifyOtpForIdentifier(phoneNumber, code);
    }
    verifyOtpForIdentifier(identifier, code) {
        const record = this.otpStore.get(identifier);
        if (!record) {
            throw new common_1.UnauthorizedException('OTP not requested or expired');
        }
        if (Date.now() > record.expiresAt) {
            this.otpStore.delete(identifier);
            throw new common_1.UnauthorizedException('OTP expired');
        }
        if (record.code !== code) {
            throw new common_1.UnauthorizedException('Invalid OTP code');
        }
        this.otpStore.delete(identifier);
    }
    getResendWaitTime(identifier) {
        const record = this.otpStore.get(identifier);
        if (!record?.lastSentAt) {
            return 0;
        }
        const timeSinceLastSend = Date.now() - record.lastSentAt;
        if (timeSinceLastSend >= this.resendDelayMs) {
            return 0;
        }
        return Math.ceil((this.resendDelayMs - timeSinceLastSend) / 1000);
    }
};
exports.OtpService = OtpService;
exports.OtpService = OtpService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [config_1.ConfigService])
], OtpService);
//# sourceMappingURL=otp.service.js.map
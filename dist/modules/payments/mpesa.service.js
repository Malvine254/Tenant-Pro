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
var MpesaService_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.MpesaService = void 0;
const common_1 = require("@nestjs/common");
const config_1 = require("@nestjs/config");
let MpesaService = MpesaService_1 = class MpesaService {
    constructor(configService) {
        this.configService = configService;
        this.logger = new common_1.Logger(MpesaService_1.name);
    }
    baseUrl() {
        const env = this.configService.get('MPESA_ENV', 'sandbox');
        return env === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }
    async getAccessToken() {
        const consumerKey = this.configService.getOrThrow('MPESA_CONSUMER_KEY');
        const consumerSecret = this.configService.getOrThrow('MPESA_CONSUMER_SECRET');
        const credentials = Buffer.from(`${consumerKey}:${consumerSecret}`).toString('base64');
        const response = await fetch(`${this.baseUrl()}/oauth/v1/generate?grant_type=client_credentials`, {
            headers: {
                Authorization: `Basic ${credentials}`,
            },
        });
        if (!response.ok) {
            const text = await response.text();
            this.logger.error(`M-Pesa token fetch failed: ${text}`);
            throw new common_1.InternalServerErrorException('Failed to obtain M-Pesa access token');
        }
        const data = (await response.json());
        return data.access_token;
    }
    timestamp() {
        const now = new Date();
        const pad = (n) => n.toString().padStart(2, '0');
        return (`${now.getFullYear()}` +
            `${pad(now.getMonth() + 1)}` +
            `${pad(now.getDate())}` +
            `${pad(now.getHours())}` +
            `${pad(now.getMinutes())}` +
            `${pad(now.getSeconds())}`);
    }
    password(timestamp) {
        const shortCode = this.configService.getOrThrow('MPESA_SHORTCODE');
        const passkey = this.configService.getOrThrow('MPESA_PASSKEY');
        return Buffer.from(`${shortCode}${passkey}${timestamp}`).toString('base64');
    }
    normalisePhone(phone) {
        return phone.replace(/^\+/, '').replace(/^0/, '254');
    }
    async stkPush(options) {
        const token = await this.getAccessToken();
        const shortCode = this.configService.getOrThrow('MPESA_SHORTCODE');
        const callbackUrl = this.configService.getOrThrow('MPESA_CALLBACK_URL');
        const ts = this.timestamp();
        const body = {
            BusinessShortCode: shortCode,
            Password: this.password(ts),
            Timestamp: ts,
            TransactionType: 'CustomerPayBillOnline',
            Amount: Math.ceil(options.amount),
            PartyA: this.normalisePhone(options.phoneNumber),
            PartyB: shortCode,
            PhoneNumber: this.normalisePhone(options.phoneNumber),
            CallBackURL: callbackUrl,
            AccountReference: options.accountReference,
            TransactionDesc: options.transactionDesc,
        };
        const response = await fetch(`${this.baseUrl()}/mpesa/stkpush/v1/processrequest`, {
            method: 'POST',
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(body),
        });
        if (!response.ok) {
            const text = await response.text();
            this.logger.error(`STK Push failed: ${text}`);
            throw new common_1.InternalServerErrorException('M-Pesa STK Push request failed');
        }
        const result = (await response.json());
        this.logger.log(`STK Push sent – MerchantRequestID: ${result.MerchantRequestID}, CheckoutRequestID: ${result.CheckoutRequestID}`);
        return result;
    }
};
exports.MpesaService = MpesaService;
exports.MpesaService = MpesaService = MpesaService_1 = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [config_1.ConfigService])
], MpesaService);
//# sourceMappingURL=mpesa.service.js.map
import { ConfigService } from '@nestjs/config';
type StkPushResult = {
    MerchantRequestID: string;
    CheckoutRequestID: string;
    ResponseCode: string;
    ResponseDescription: string;
    CustomerMessage: string;
};
export declare class MpesaService {
    private readonly configService;
    private readonly logger;
    constructor(configService: ConfigService);
    private baseUrl;
    private getAccessToken;
    private timestamp;
    private password;
    private normalisePhone;
    stkPush(options: {
        phoneNumber: string;
        amount: number;
        accountReference: string;
        transactionDesc: string;
    }): Promise<StkPushResult>;
}
export {};

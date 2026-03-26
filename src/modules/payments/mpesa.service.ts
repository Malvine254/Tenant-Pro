import { Injectable, InternalServerErrorException, Logger } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';

type StkPushResult = {
  MerchantRequestID: string;
  CheckoutRequestID: string;
  ResponseCode: string;
  ResponseDescription: string;
  CustomerMessage: string;
};

@Injectable()
export class MpesaService {
  private readonly logger = new Logger(MpesaService.name);

  constructor(private readonly configService: ConfigService) {}

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  private baseUrl(): string {
    const env = this.configService.get<string>('MPESA_ENV', 'sandbox');
    return env === 'production'
      ? 'https://api.safaricom.co.ke'
      : 'https://sandbox.safaricom.co.ke';
  }

  private async getAccessToken(): Promise<string> {
    const consumerKey = this.configService.getOrThrow<string>('MPESA_CONSUMER_KEY');
    const consumerSecret = this.configService.getOrThrow<string>('MPESA_CONSUMER_SECRET');

    const credentials = Buffer.from(`${consumerKey}:${consumerSecret}`).toString('base64');

    const response = await fetch(
      `${this.baseUrl()}/oauth/v1/generate?grant_type=client_credentials`,
      {
        headers: {
          Authorization: `Basic ${credentials}`,
        },
      },
    );

    if (!response.ok) {
      const text = await response.text();
      this.logger.error(`M-Pesa token fetch failed: ${text}`);
      throw new InternalServerErrorException('Failed to obtain M-Pesa access token');
    }

    const data = (await response.json()) as { access_token: string };
    return data.access_token;
  }

  private timestamp(): string {
    const now = new Date();
    const pad = (n: number) => n.toString().padStart(2, '0');
    return (
      `${now.getFullYear()}` +
      `${pad(now.getMonth() + 1)}` +
      `${pad(now.getDate())}` +
      `${pad(now.getHours())}` +
      `${pad(now.getMinutes())}` +
      `${pad(now.getSeconds())}`
    );
  }

  private password(timestamp: string): string {
    const shortCode = this.configService.getOrThrow<string>('MPESA_SHORTCODE');
    const passkey = this.configService.getOrThrow<string>('MPESA_PASSKEY');
    return Buffer.from(`${shortCode}${passkey}${timestamp}`).toString('base64');
  }

  private normalisePhone(phone: string): string {
    // Convert 07XXXXXXXX → 2547XXXXXXXX, strip leading +
    return phone.replace(/^\+/, '').replace(/^0/, '254');
  }

  // ---------------------------------------------------------------------------
  // Public API
  // ---------------------------------------------------------------------------

  async stkPush(options: {
    phoneNumber: string;
    amount: number;
    accountReference: string;
    transactionDesc: string;
  }): Promise<StkPushResult> {
    const token = await this.getAccessToken();
    const shortCode = this.configService.getOrThrow<string>('MPESA_SHORTCODE');
    const callbackUrl = this.configService.getOrThrow<string>('MPESA_CALLBACK_URL');
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
      throw new InternalServerErrorException('M-Pesa STK Push request failed');
    }

    const result = (await response.json()) as StkPushResult;
    this.logger.log(
      `STK Push sent – MerchantRequestID: ${result.MerchantRequestID}, CheckoutRequestID: ${result.CheckoutRequestID}`,
    );
    return result;
  }
}

/**
 * Mirrors the M-Pesa Daraja STK Push callback payload structure.
 * Properties are intentionally loose (Record<string, unknown>) because
 * Safaricom may omit or reorder fields in error scenarios.
 */
export class MpesaCallbackDto {
  Body!: {
    stkCallback: {
      MerchantRequestID: string;
      CheckoutRequestID: string;
      ResultCode: number;
      ResultDesc: string;
      CallbackMetadata?: {
        Item: Array<{ Name: string; Value?: string | number }>;
      };
    };
  };
}

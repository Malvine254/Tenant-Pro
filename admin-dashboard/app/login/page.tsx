"use client";

import { FormEvent, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiRequest } from '../../lib/api';
import { saveSession } from '../../lib/auth';

type OtpRequestResponse = {
  message: string;
  phoneNumber: string;
  otpCode: string;
  expiresAt: string;
};

type VerifyResponse = {
  accessToken: string;
  tokenType: string;
  user: {
    id: string;
    phoneNumber: string;
    email?: string | null;
    firstName?: string | null;
    lastName?: string | null;
    role: 'LANDLORD' | 'TENANT' | 'ADMIN' | 'CARETAKER';
  };
};

export default function LoginPage() {
  const router = useRouter();
  const [phoneNumber, setPhoneNumber] = useState('+254700000099');
  const [otpCode, setOtpCode] = useState('');
  const [serverOtp, setServerOtp] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const requestOtp = async (event: FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const response = await apiRequest<OtpRequestResponse>('/auth/otp/request', undefined, {
        method: 'POST',
        body: JSON.stringify({ phoneNumber: phoneNumber.trim() }),
      });
      setServerOtp(response.otpCode);
      setOtpCode(response.otpCode);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to request OTP');
    } finally {
      setLoading(false);
    }
  };

  const verifyOtp = async (event: FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setError(null);

    const normalizedPhoneNumber = phoneNumber.trim();
    const normalizedOtpCode = otpCode.trim().replace(/\D/g, '').slice(0, 6);

    if (normalizedOtpCode.length !== 6) {
      setError('OTP code must be exactly 6 digits.');
      setLoading(false);
      return;
    }

    try {
      const response = await apiRequest<VerifyResponse>('/auth/otp/verify', undefined, {
        method: 'POST',
        body: JSON.stringify({ phoneNumber: normalizedPhoneNumber, code: normalizedOtpCode }),
      });

      if (response.user.role !== 'ADMIN') {
        setError('This page requires an ADMIN account. Use +254700000099 to sign in.');
        return;
      }

      saveSession({ accessToken: response.accessToken, user: response.user });
      router.push('/dashboard');
    } catch (verifyError) {
      setError(verifyError instanceof Error ? verifyError.message : 'Failed to verify OTP');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-6 text-gray-900">
      <h1 className="mb-2 text-2xl font-semibold text-gray-900">Admin Login (JWT)</h1>
      <p className="mb-3 text-sm text-gray-600">Request OTP then verify to receive JWT token.</p>
      <p className="mb-8 rounded-md bg-blue-50 px-3 py-2 text-xs text-blue-700">Use the seeded admin phone <span className="font-semibold">+254700000099</span> for dashboard access.</p>

      <form onSubmit={requestOtp} className="tp-form-panel">
        <label className="block text-sm font-medium text-zinc-800">Phone Number</label>
        <input
          value={phoneNumber}
          onChange={(event) => setPhoneNumber(event.target.value.trimStart())}
          className="tp-input"
          placeholder="+2547XXXXXXXX"
          required
        />
        <button
          type="submit"
          disabled={loading}
          className="tp-primary-btn disabled:opacity-50"
        >
          {loading ? 'Requesting...' : 'Request OTP'}
        </button>
      </form>

      {serverOtp ? (
        <p className="mt-3 rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-700">
          Simulation OTP: <span className="font-semibold">{serverOtp}</span>
        </p>
      ) : null}

      <form onSubmit={verifyOtp} className="tp-form-panel mt-4">
        <label className="block text-sm font-medium text-zinc-800">OTP Code</label>
        <input
          value={otpCode}
          onChange={(event) => setOtpCode(event.target.value.replace(/\D/g, '').slice(0, 6))}
          className="tp-input"
          placeholder="6-digit OTP"
          inputMode="numeric"
          maxLength={6}
          required
        />
        <button
          type="submit"
          disabled={loading}
          className="tp-primary-btn disabled:opacity-50"
        >
          {loading ? 'Verifying...' : 'Verify OTP & Login'}
        </button>
      </form>

      {error ? <p className="mt-4 text-sm text-red-600">{error}</p> : null}
    </main>
  );
}

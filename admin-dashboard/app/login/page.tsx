"use client";

import { FormEvent, useMemo, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { apiRequest } from '../../lib/api';
import { saveDemoSession, saveSession } from '../../lib/auth';

type AuthResponse = {
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

type RegisterResponse = {
  message: string;
  email: string;
};

type DemoResponse = {
  message: string;
  expiresAt: string;
};

type MessageResponse = {
  message: string;
};

type TabKey = 'signin' | 'register' | 'verify' | 'forgot' | 'reset' | 'demo';

export default function LoginPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const isDemoRequest = searchParams.get('mode') === 'demo';

  const [activeTab, setActiveTab] = useState<TabKey>(isDemoRequest ? 'demo' : 'signin');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [pendingVerificationEmail, setPendingVerificationEmail] = useState('');
  const [verificationCode, setVerificationCode] = useState('');

  const [loginForm, setLoginForm] = useState({
    email: '',
    password: '',
  });

  const [registerForm, setRegisterForm] = useState({
    firstName: '',
    lastName: '',
    email: '',
    phoneNumber: '',
    password: '',
    confirmPassword: '',
    role: 'LANDLORD',
  });

  const [demoForm, setDemoForm] = useState({
    name: '',
    email: '',
  });

  const [forgotForm, setForgotForm] = useState({ email: '' });
  const [resetForm, setResetForm] = useState({
    email: '',
    code: '',
    newPassword: '',
    confirmPassword: '',
  });
  const [showLoginPassword, setShowLoginPassword] = useState(false);
  const [showRegisterPassword, setShowRegisterPassword] = useState(false);
  const [showRegisterConfirmPassword, setShowRegisterConfirmPassword] = useState(false);
  const [showResetPassword, setShowResetPassword] = useState(false);
  const [showResetConfirmPassword, setShowResetConfirmPassword] = useState(false);

  const pageTitle = useMemo(
    () => (isDemoRequest ? 'Request demo access' : 'Sign in to Starmax'),
    [isDemoRequest],
  );

  const normalizedRegisterEmail = registerForm.email.trim().toLowerCase();
  const normalizedForgotEmail = forgotForm.email.trim().toLowerCase();
  const normalizedResetEmail = resetForm.email.trim().toLowerCase();
  const registerPasswordsMatch =
    registerForm.confirmPassword.length === 0 || registerForm.password === registerForm.confirmPassword;
  const resetPasswordsMatch =
    resetForm.confirmPassword.length === 0 || resetForm.newPassword === resetForm.confirmPassword;

  const handleLogin = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      const response = await apiRequest<AuthResponse>('/auth/login', undefined, {
        method: 'POST',
        body: JSON.stringify({
          email: loginForm.email.trim().toLowerCase(),
          password: loginForm.password,
        }),
      });

      const isDemoUser = response.user.email?.toLowerCase().endsWith('@starmax.preview');

      if (isDemoUser) {
        saveDemoSession({ accessToken: response.accessToken, user: response.user });
        router.push('/dashboard?mode=demo');
        return;
      }

      saveSession({ accessToken: response.accessToken, user: response.user });
      router.push('/dashboard');
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Unable to sign in.');
    } finally {
      setLoading(false);
    }
  };

  const handleRegister = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (registerForm.password !== registerForm.confirmPassword) {
      setError('Passwords do not match.');
      return;
    }

    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      const response = await apiRequest<RegisterResponse>('/auth/register', undefined, {
        method: 'POST',
        body: JSON.stringify({
          ...registerForm,
          email: normalizedRegisterEmail,
          phoneNumber: registerForm.phoneNumber.trim() || undefined,
        }),
      });

      setPendingVerificationEmail(response.email);
      setActiveTab('verify');
      setMessage(response.message);
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Unable to create your account.');
    } finally {
      setLoading(false);
    }
  };

  const handleForgotPassword = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      const response = await apiRequest<MessageResponse>('/auth/forgot-password', undefined, {
        method: 'POST',
        body: JSON.stringify({ email: normalizedForgotEmail }),
      });

      setResetForm((current) => ({ ...current, email: normalizedForgotEmail }));
      setActiveTab('reset');
      setMessage(response.message || 'Reset code sent. Check your email.');
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Unable to send reset code.');
    } finally {
      setLoading(false);
    }
  };

  const handleResetPassword = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (resetForm.newPassword !== resetForm.confirmPassword) {
      setError('Passwords do not match.');
      return;
    }

    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      const response = await apiRequest<MessageResponse>('/auth/reset-password', undefined, {
        method: 'POST',
        body: JSON.stringify({
          email: normalizedResetEmail,
          code: resetForm.code.trim(),
          newPassword: resetForm.newPassword,
        }),
      });

      setLoginForm((current) => ({ ...current, email: normalizedResetEmail, password: '' }));
      setActiveTab('signin');
      setResetForm({ email: normalizedResetEmail, code: '', newPassword: '', confirmPassword: '' });
      setMessage(response.message || 'Password reset successful. You can now sign in.');
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Unable to reset password.');
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyEmail = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const response = await apiRequest<AuthResponse>('/auth/email-otp/verify', undefined, {
        method: 'POST',
        body: JSON.stringify({
          email: pendingVerificationEmail,
          code: verificationCode.trim(),
        }),
      });

      saveSession({ accessToken: response.accessToken, user: response.user });
      router.push('/dashboard');
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Verification failed.');
    } finally {
      setLoading(false);
    }
  };

  const resendVerification = async () => {
    if (!pendingVerificationEmail) return;

    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      await apiRequest('/auth/email-otp/request', undefined, {
        method: 'POST',
        body: JSON.stringify({ email: pendingVerificationEmail }),
      });
      setMessage('A fresh verification code has been sent to your email.');
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Unable to resend verification code.');
    } finally {
      setLoading(false);
    }
  };

  const handleDemoRequest = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      const response = await apiRequest<DemoResponse>('/auth/demo/request', undefined, {
        method: 'POST',
        body: JSON.stringify({
          email: demoForm.email.trim().toLowerCase(),
          name: demoForm.name.trim(),
        }),
      });

      setMessage(`${response.message} Check your inbox for the login email and temporary password.`);
      setActiveTab('signin');
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Unable to request demo access.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(99,102,241,0.16),_transparent_0,_transparent_52%),linear-gradient(to_bottom,_#ffffff,_#f8fafc)] px-6 py-10 text-gray-900">
      <div className="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
        <section className="space-y-6">
          <div className="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-indigo-700">
            {isDemoRequest ? 'Demo access' : 'Secure access'}
          </div>

          <div>
            <h1 className="text-4xl font-semibold tracking-tight text-zinc-950 sm:text-5xl">{pageTitle}</h1>
            <p className="mt-4 max-w-xl text-base leading-7 text-zinc-600">
              Use email and password for secure sign-in, request limited demo credentials by email, or create a new verified account with Starmax.
            </p>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg">
              <p className="text-sm font-semibold text-zinc-900">Email + password login</p>
              <p className="mt-2 text-sm text-zinc-600">OTP is no longer required for the main sign-in flow.</p>
            </div>
            <div className="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg">
              <p className="text-sm font-semibold text-zinc-900">1-hour demo access</p>
              <p className="mt-2 text-sm text-zinc-600">Temporary credentials are emailed and isolated from real accounts.</p>
            </div>
          </div>
        </section>

        <section className="tp-form-panel rounded-[28px] border border-zinc-200 bg-white/95 p-6 shadow-[0_24px_80px_-28px_rgba(15,23,42,0.3)] backdrop-blur sm:p-7">
          <div className="mb-5 flex flex-wrap gap-2">
            {[
              { key: 'signin', label: 'Sign in' },
              { key: 'register', label: 'Create account' },
              { key: 'forgot', label: 'Reset password' },
              { key: 'demo', label: 'Request demo' },
              ...(pendingVerificationEmail ? [{ key: 'verify', label: 'Verify email' }] : []),
              ...(resetForm.email ? [{ key: 'reset', label: 'Enter reset code' }] : []),
            ].map((tab) => {
              const active = activeTab === tab.key;
              return (
                <button
                  key={tab.key}
                  type="button"
                  onClick={() => setActiveTab(tab.key as TabKey)}
                  className={`rounded-full px-4 py-2 text-sm font-medium transition ${
                    active
                      ? 'bg-zinc-950 text-white shadow-lg shadow-zinc-950/10'
                      : 'border border-zinc-200 bg-white text-zinc-700 hover:border-indigo-200 hover:text-indigo-700'
                  }`}
                >
                  {tab.label}
                </button>
              );
            })}
          </div>

          {activeTab === 'signin' ? (
            <form onSubmit={handleLogin} className="space-y-4" suppressHydrationWarning>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Email</label>
                <input
                  type="email"
                  className="tp-input"
                  autoComplete="email"
                  suppressHydrationWarning
                  value={loginForm.email}
                  onChange={(event) => setLoginForm((current) => ({ ...current, email: event.target.value }))}
                  placeholder={isDemoRequest ? 'Enter emailed demo login' : 'you@example.com'}
                  required
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Password</label>
                <div className="relative">
                  <input
                    type={showLoginPassword ? 'text' : 'password'}
                    className="tp-input tp-input-with-affordance"
                    autoComplete="current-password"
                    suppressHydrationWarning
                    value={loginForm.password}
                    onChange={(event) => setLoginForm((current) => ({ ...current, password: event.target.value }))}
                    placeholder="Enter your password"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowLoginPassword((current) => !current)}
                    className="absolute inset-y-0 right-3 text-xs font-semibold text-zinc-500 transition hover:text-zinc-900"
                  >
                    {showLoginPassword ? 'Hide' : 'Show'}
                  </button>
                </div>
              </div>
              <div className="flex items-center justify-between gap-3">
                <p className="tp-field-note">Use the same email address you verified for your workspace.</p>
                <button type="button" onClick={() => setActiveTab('forgot')} className="tp-inline-link">
                  Forgot password?
                </button>
              </div>
              <button type="submit" disabled={loading} className="tp-primary-btn disabled:opacity-60">
                {loading ? 'Signing in...' : isDemoRequest ? 'Enter demo workspace' : 'Sign in'}
              </button>
            </form>
          ) : null}

          {activeTab === 'register' ? (
            <form onSubmit={handleRegister} className="space-y-4" suppressHydrationWarning>
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-1 block text-sm font-medium text-zinc-800">First name</label>
                  <input className="tp-input" suppressHydrationWarning value={registerForm.firstName} onChange={(event) => setRegisterForm((current) => ({ ...current, firstName: event.target.value }))} required />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium text-zinc-800">Last name</label>
                  <input className="tp-input" suppressHydrationWarning value={registerForm.lastName} onChange={(event) => setRegisterForm((current) => ({ ...current, lastName: event.target.value }))} required />
                </div>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Email</label>
                <input type="email" className="tp-input" suppressHydrationWarning value={registerForm.email} onChange={(event) => setRegisterForm((current) => ({ ...current, email: event.target.value }))} required />
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-1 block text-sm font-medium text-zinc-800">Phone (optional)</label>
                  <input className="tp-input" suppressHydrationWarning value={registerForm.phoneNumber} onChange={(event) => setRegisterForm((current) => ({ ...current, phoneNumber: event.target.value }))} placeholder="+2547..." />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium text-zinc-800">Role</label>
                  <select className="tp-select" suppressHydrationWarning value={registerForm.role} onChange={(event) => setRegisterForm((current) => ({ ...current, role: event.target.value }))}>
                    <option value="LANDLORD">Landlord</option>
                    <option value="TENANT">Tenant</option>
                    <option value="CARETAKER">Caretaker</option>
                  </select>
                </div>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Password</label>
                <div className="relative">
                  <input type={showRegisterPassword ? 'text' : 'password'} className="tp-input tp-input-with-affordance" suppressHydrationWarning value={registerForm.password} onChange={(event) => setRegisterForm((current) => ({ ...current, password: event.target.value }))} minLength={8} required />
                  <button
                    type="button"
                    onClick={() => setShowRegisterPassword((current) => !current)}
                    className="absolute inset-y-0 right-3 text-xs font-semibold text-zinc-500 transition hover:text-zinc-900"
                  >
                    {showRegisterPassword ? 'Hide' : 'Show'}
                  </button>
                </div>
                <p className="mt-2 tp-field-note">Use at least 8 characters for a production-ready account password.</p>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Confirm password</label>
                <div className="relative">
                  <input type={showRegisterConfirmPassword ? 'text' : 'password'} className="tp-input tp-input-with-affordance" suppressHydrationWarning value={registerForm.confirmPassword} onChange={(event) => setRegisterForm((current) => ({ ...current, confirmPassword: event.target.value }))} minLength={8} required />
                  <button
                    type="button"
                    onClick={() => setShowRegisterConfirmPassword((current) => !current)}
                    className="absolute inset-y-0 right-3 text-xs font-semibold text-zinc-500 transition hover:text-zinc-900"
                  >
                    {showRegisterConfirmPassword ? 'Hide' : 'Show'}
                  </button>
                </div>
                {!registerPasswordsMatch ? <p className="mt-2 tp-field-error">Passwords must match before the account can be created.</p> : null}
              </div>
              <button type="submit" disabled={loading || !registerPasswordsMatch || registerForm.password.length < 8} className="tp-primary-btn disabled:opacity-60">
                {loading ? 'Creating account...' : 'Create account & send verification'}
              </button>
            </form>
          ) : null}

          {activeTab === 'forgot' ? (
            <form onSubmit={handleForgotPassword} className="space-y-4" suppressHydrationWarning>
              <div className="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Enter the email tied to your account and we&apos;ll send a one-time reset code.
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Account email</label>
                <input
                  type="email"
                  className="tp-input"
                  suppressHydrationWarning
                  value={forgotForm.email}
                  onChange={(event) => setForgotForm({ email: event.target.value })}
                  required
                />
              </div>
              <button type="submit" disabled={loading} className="tp-primary-btn disabled:opacity-60">
                {loading ? 'Sending reset code...' : 'Send reset code'}
              </button>
            </form>
          ) : null}

          {activeTab === 'reset' ? (
            <form onSubmit={handleResetPassword} className="space-y-4" suppressHydrationWarning>
              <div className="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
                Use the code from your inbox to set a new password.
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Email</label>
                <input
                  type="email"
                  className="tp-input"
                  suppressHydrationWarning
                  value={resetForm.email}
                  onChange={(event) => setResetForm((current) => ({ ...current, email: event.target.value }))}
                  required
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Reset code</label>
                <input
                  className="tp-input"
                  suppressHydrationWarning
                  value={resetForm.code}
                  onChange={(event) => setResetForm((current) => ({ ...current, code: event.target.value.replace(/\D/g, '').slice(0, 6) }))}
                  inputMode="numeric"
                  maxLength={6}
                  placeholder="6-digit code"
                  required
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">New password</label>
                <div className="relative">
                  <input
                    type={showResetPassword ? 'text' : 'password'}
                    className="tp-input tp-input-with-affordance"
                    suppressHydrationWarning
                    value={resetForm.newPassword}
                    onChange={(event) => setResetForm((current) => ({ ...current, newPassword: event.target.value }))}
                    minLength={8}
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowResetPassword((current) => !current)}
                    className="absolute inset-y-0 right-3 text-xs font-semibold text-zinc-500 transition hover:text-zinc-900"
                  >
                    {showResetPassword ? 'Hide' : 'Show'}
                  </button>
                </div>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Confirm new password</label>
                <div className="relative">
                  <input
                    type={showResetConfirmPassword ? 'text' : 'password'}
                    className="tp-input tp-input-with-affordance"
                    suppressHydrationWarning
                    value={resetForm.confirmPassword}
                    onChange={(event) => setResetForm((current) => ({ ...current, confirmPassword: event.target.value }))}
                    minLength={8}
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowResetConfirmPassword((current) => !current)}
                    className="absolute inset-y-0 right-3 text-xs font-semibold text-zinc-500 transition hover:text-zinc-900"
                  >
                    {showResetConfirmPassword ? 'Hide' : 'Show'}
                  </button>
                </div>
                {!resetPasswordsMatch ? <p className="mt-2 tp-field-error">Passwords must match before reset can continue.</p> : null}
              </div>
              <div className="flex flex-col gap-3 sm:flex-row">
                <button type="submit" disabled={loading || !resetPasswordsMatch || resetForm.newPassword.length < 8} className="tp-primary-btn disabled:opacity-60 sm:w-auto sm:px-5">
                  {loading ? 'Resetting password...' : 'Reset password'}
                </button>
                <button type="button" onClick={() => setActiveTab('signin')} className="tp-secondary-btn sm:w-auto sm:px-5">
                  Back to sign in
                </button>
              </div>
            </form>
          ) : null}

          {activeTab === 'verify' ? (
            <form onSubmit={handleVerifyEmail} className="space-y-4" suppressHydrationWarning>
              <div className="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
                Verification code sent to <strong>{pendingVerificationEmail}</strong>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Email verification code</label>
                <input
                  className="tp-input"
                  suppressHydrationWarning
                  value={verificationCode}
                  onChange={(event) => setVerificationCode(event.target.value.replace(/\D/g, '').slice(0, 6))}
                  inputMode="numeric"
                  maxLength={6}
                  placeholder="6-digit code"
                  required
                />
              </div>
              <div className="flex flex-col gap-3 sm:flex-row">
                <button type="submit" disabled={loading} className="tp-primary-btn disabled:opacity-60 sm:w-auto sm:px-5">
                  {loading ? 'Verifying...' : 'Verify email & continue'}
                </button>
                <button type="button" onClick={resendVerification} disabled={loading} className="tp-secondary-btn sm:w-auto sm:px-5">
                  Resend code
                </button>
              </div>
            </form>
          ) : null}

          {activeTab === 'demo' ? (
            <form onSubmit={handleDemoRequest} className="space-y-4" suppressHydrationWarning>
              <div className="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                A limited demo account will be emailed to you and will expire after 1 hour. Credentials are not shown on screen.
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Your name</label>
                <input className="tp-input" suppressHydrationWarning value={demoForm.name} onChange={(event) => setDemoForm((current) => ({ ...current, name: event.target.value }))} required />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-zinc-800">Your email</label>
                <input type="email" className="tp-input" suppressHydrationWarning value={demoForm.email} onChange={(event) => setDemoForm((current) => ({ ...current, email: event.target.value }))} required />
              </div>
              <button type="submit" disabled={loading} className="tp-primary-btn disabled:opacity-60">
                {loading ? 'Sending demo credentials...' : 'Email my 1-hour demo access'}
              </button>
            </form>
          ) : null}

          {message ? <p className="mt-4 text-sm text-emerald-700">{message}</p> : null}
          {error ? <p className="mt-4 text-sm text-red-600">{error}</p> : null}
        </section>
      </div>
    </main>
  );
}

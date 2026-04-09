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
var EmailService_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.EmailService = void 0;
const common_1 = require("@nestjs/common");
const config_1 = require("@nestjs/config");
const nodemailer = __importStar(require("nodemailer"));
let EmailService = EmailService_1 = class EmailService {
    constructor(configService) {
        this.configService = configService;
        this.logger = new common_1.Logger(EmailService_1.name);
        this.transporter = nodemailer.createTransport({
            host: this.configService.get('MAIL_HOST'),
            port: parseInt(this.configService.get('MAIL_PORT', '465')),
            secure: this.configService.get('MAIL_SECURE') === 'true',
            auth: {
                user: this.configService.get('MAIL_USER'),
                pass: this.configService.get('MAIL_PASSWORD'),
            },
        });
    }
    async sendOtpEmail(email, otp, firstName) {
        const fromName = this.configService.get('MAIL_FROM_NAME');
        const fromEmail = this.configService.get('MAIL_FROM_EMAIL');
        const expiryMinutes = this.configService.get('OTP_EXPIRY_MINUTES', '10');
        const subject = 'Your Starmax verification code';
        const html = this.getOtpEmailTemplate(otp, firstName, expiryMinutes);
        try {
            await this.transporter.sendMail({
                from: `"${fromName}" <${fromEmail}>`,
                to: email,
                subject,
                html,
            });
            this.logger.log(`OTP email sent to ${email}`);
            return { success: true, message: 'OTP sent successfully' };
        }
        catch (error) {
            this.logger.error(`Failed to send OTP email to ${email}:`, error);
            throw new Error('Failed to send OTP email');
        }
    }
    async sendVerificationEmail(email, otp, firstName) {
        return this.sendOtpEmail(email, otp, firstName);
    }
    async sendDemoAccessEmail(email, loginEmail, password, expiresAt, firstName) {
        const fromName = this.configService.get('MAIL_FROM_NAME');
        const fromEmail = this.configService.get('MAIL_FROM_EMAIL');
        const subject = 'Your Starmax demo credentials';
        const html = this.getDemoAccessTemplate(loginEmail, password, expiresAt, firstName);
        try {
            await this.transporter.sendMail({
                from: `"${fromName}" <${fromEmail}>`,
                to: email,
                subject,
                html,
            });
            this.logger.log(`Demo access email sent to ${email}`);
            return { success: true, message: 'Demo access email sent' };
        }
        catch (error) {
            this.logger.error(`Failed to send demo access email to ${email}:`, error);
            throw new Error('Failed to send demo access email');
        }
    }
    async sendPasswordResetEmail(email, resetToken, firstName) {
        const fromName = this.configService.get('MAIL_FROM_NAME');
        const fromEmail = this.configService.get('MAIL_FROM_EMAIL');
        const resetLink = `${this.configService.get('FRONTEND_URL', 'http://localhost:3001')}/reset-password?token=${resetToken}`;
        const subject = 'Reset Your Password';
        const html = this.getPasswordResetTemplate(resetLink, firstName);
        try {
            await this.transporter.sendMail({
                from: `"${fromName}" <${fromEmail}>`,
                to: email,
                subject,
                html,
            });
            this.logger.log(`Password reset email sent to ${email}`);
            return { success: true, message: 'Reset email sent' };
        }
        catch (error) {
            this.logger.error(`Failed to send reset email to ${email}:`, error);
            throw new Error('Failed to send password reset email');
        }
    }
    async sendWelcomeEmail(email, firstName) {
        const fromName = this.configService.get('MAIL_FROM_NAME');
        const fromEmail = this.configService.get('MAIL_FROM_EMAIL');
        const subject = `Welcome to ${fromName}!`;
        const html = this.getWelcomeEmailTemplate(firstName);
        try {
            await this.transporter.sendMail({
                from: `"${fromName}" <${fromEmail}>`,
                to: email,
                subject,
                html,
            });
            this.logger.log(`Welcome email sent to ${email}`);
        }
        catch (error) {
            this.logger.error(`Failed to send welcome email to ${email}:`, error);
        }
    }
    async sendRentReminderEmail(email, firstName, amount, dueDate, propertyName) {
        const fromName = this.configService.get('MAIL_FROM_NAME');
        const fromEmail = this.configService.get('MAIL_FROM_EMAIL');
        const subject = 'Rent Payment Reminder';
        const html = this.getRentReminderTemplate(firstName, amount, dueDate, propertyName);
        try {
            await this.transporter.sendMail({
                from: `"${fromName}" <${fromEmail}>`,
                to: email,
                subject,
                html,
            });
            this.logger.log(`Rent reminder sent to ${email}`);
            return { success: true };
        }
        catch (error) {
            this.logger.error(`Failed to send rent reminder to ${email}:`, error);
            return { success: false };
        }
    }
    async sendOverdueNoticeEmail(email, firstName, amount, daysOverdue, propertyName) {
        const fromName = this.configService.get('MAIL_FROM_NAME');
        const fromEmail = this.configService.get('MAIL_FROM_EMAIL');
        const subject = 'Overdue Payment Notice';
        const html = this.getOverdueNoticeTemplate(firstName, amount, daysOverdue, propertyName);
        try {
            await this.transporter.sendMail({
                from: `"${fromName}" <${fromEmail}>`,
                to: email,
                subject,
                html,
            });
            this.logger.log(`Overdue notice sent to ${email}`);
            return { success: true };
        }
        catch (error) {
            this.logger.error(`Failed to send overdue notice to ${email}:`, error);
            return { success: false };
        }
    }
    async sendMaintenanceUpdateEmail(email, firstName, requestId, status, description) {
        const fromName = this.configService.get('MAIL_FROM_NAME');
        const fromEmail = this.configService.get('MAIL_FROM_EMAIL');
        const subject = `Maintenance Request Update - #${requestId}`;
        const html = this.getMaintenanceUpdateTemplate(firstName, requestId, status, description);
        try {
            await this.transporter.sendMail({
                from: `"${fromName}" <${fromEmail}>`,
                to: email,
                subject,
                html,
            });
            this.logger.log(`Maintenance update sent to ${email}`);
            return { success: true };
        }
        catch (error) {
            this.logger.error(`Failed to send maintenance update to ${email}:`, error);
            return { success: false };
        }
    }
    getOtpEmailTemplate(otp, firstName, expiryMinutes) {
        const greeting = firstName ? `Hi ${firstName},` : 'Hi,';
        return `
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body { margin: 0; padding: 0; background: #f4f7fb; font-family: Arial, Helvetica, sans-serif; color: #1f2937; }
    .shell { padding: 24px 12px; }
    .card { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08); }
    .hero { background: linear-gradient(135deg, #0f172a, #312e81); color: #ffffff; padding: 28px 32px; }
    .hero h1 { margin: 0; font-size: 24px; }
    .hero p { margin: 8px 0 0; color: #dbeafe; font-size: 14px; }
    .content { padding: 28px 32px; }
    .otp-box { margin: 22px 0; border-radius: 16px; background: linear-gradient(135deg, #eef2ff, #eff6ff); border: 1px solid #c7d2fe; text-align: center; padding: 18px; }
    .otp-code { font-size: 32px; letter-spacing: 8px; font-weight: 700; color: #312e81; }
    .note { margin-top: 14px; font-size: 14px; color: #475569; }
    .footer { border-top: 1px solid #e5e7eb; padding: 18px 32px 24px; font-size: 12px; color: #64748b; }
  </style>
</head>
<body>
  <div class="shell">
    <div class="card">
      <div class="hero">
        <h1>Email verification</h1>
        <p>Secure access for your Starmax account</p>
      </div>
      <div class="content">
        <p>${greeting}</p>
        <p>Use the verification code below to complete your sign-in or confirm your email address.</p>
        <div class="otp-box">
          <div class="otp-code">${otp}</div>
        </div>
        <p class="note"><strong>This code expires in ${expiryMinutes} minutes.</strong></p>
        <p class="note">If you did not request this code, you can safely ignore this message.</p>
      </div>
      <div class="footer">
        <p>This is an automated email from Starmax Ltd. Please do not reply directly.</p>
        <p>&copy; ${new Date().getFullYear()} Starmax Ltd. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
    `;
    }
    getDemoAccessTemplate(loginEmail, password, expiresAt, firstName) {
        const greeting = firstName ? `Hi ${firstName},` : 'Hi,';
        const loginUrl = `${this.configService.get('FRONTEND_URL', 'http://localhost:3001')}/login?mode=demo`;
        return `
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body { margin: 0; padding: 0; background: #f4f7fb; font-family: Arial, Helvetica, sans-serif; color: #1f2937; }
    .shell { padding: 24px 12px; }
    .card { max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08); }
    .hero { background: linear-gradient(135deg, #111827, #4f46e5); color: #ffffff; padding: 30px 32px; }
    .hero h1 { margin: 0; font-size: 24px; }
    .hero p { margin: 8px 0 0; color: #dbeafe; font-size: 14px; }
    .content { padding: 28px 32px; }
    .cred-box { margin: 20px 0; border-radius: 16px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px 18px; }
    .label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; }
    .value { font-size: 16px; font-weight: 700; color: #111827; margin-top: 4px; word-break: break-word; }
    .cta { display: inline-block; margin-top: 20px; padding: 12px 18px; background: #111827; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 600; }
    .meta { margin-top: 16px; font-size: 14px; color: #475569; }
    .footer { border-top: 1px solid #e5e7eb; padding: 18px 32px 24px; font-size: 12px; color: #64748b; }
  </style>
</head>
<body>
  <div class="shell">
    <div class="card">
      <div class="hero">
        <h1>Your Starmax demo is ready</h1>
        <p>Use these temporary credentials to explore the product safely</p>
      </div>
      <div class="content">
        <p>${greeting}</p>
        <p>Below are your limited demo credentials. They are isolated from real accounts and will remain valid for <strong>1 hour</strong>.</p>

        <div class="cred-box">
          <div class="label">Login email</div>
          <div class="value">${loginEmail}</div>
        </div>

        <div class="cred-box">
          <div class="label">Temporary password</div>
          <div class="value">${password}</div>
        </div>

        <div class="meta">Expires at: <strong>${expiresAt.toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' })}</strong></div>

        <a href="${loginUrl}" class="cta">Open Demo Login</a>
      </div>
      <div class="footer">
        <p>This demo environment is isolated for safe exploration and temporary testing.</p>
        <p>&copy; ${new Date().getFullYear()} Starmax Ltd. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
    `;
    }
    getPasswordResetTemplate(resetLink, firstName) {
        const greeting = firstName ? `Hi ${firstName},` : 'Hi,';
        return `
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .btn { display: inline-block; padding: 12px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Password Reset Request</h2>
    <p>${greeting}</p>
    <p>We received a request to reset your password. Click the button below to create a new password:</p>
    <a href="${resetLink}" class="btn">Reset Password</a>
    <p>Or copy this link into your browser:</p>
    <p style="word-break: break-all; color: #666;">${resetLink}</p>
    <p><strong>This link will expire in 1 hour.</strong></p>
    <p>If you didn't request a password reset, please ignore this email or contact support.</p>
    <div class="footer">
      <p>&copy; ${new Date().getFullYear()} Starmax Ltd. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
    `;
    }
    getWelcomeEmailTemplate(firstName) {
        return `
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
    .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Welcome to Starmax Ltd!</h1>
    </div>
    <div class="content">
      <p>Hi ${firstName},</p>
      <p>Thank you for joining us! We're excited to have you on board.</p>
      <p>Your account has been successfully created and you can now:</p>
      <ul>
        <li>View your properties and units</li>
        <li>Pay rent online</li>
        <li>Submit maintenance requests</li>
        <li>Track your payment history</li>
      </ul>
      <p>If you have any questions, feel free to reach out to our support team.</p>
    </div>
    <div class="footer">
      <p>&copy; ${new Date().getFullYear()} Starmax Ltd. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
    `;
    }
    getRentReminderTemplate(firstName, amount, dueDate, propertyName) {
        const formattedDate = dueDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
        const formattedAmount = amount.toLocaleString('en-US', {
            style: 'currency',
            currency: 'KES',
        });
        return `
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .alert-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
    .amount { font-size: 24px; font-weight: bold; color: #007bff; }
    .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Rent Payment Reminder</h2>
    <p>Hi ${firstName},</p>
    <p>This is a friendly reminder that your rent payment is due soon.</p>
    <div class="alert-box">
      <p><strong>Property:</strong> ${propertyName}</p>
      <p><strong>Amount Due:</strong> <span class="amount">${formattedAmount}</span></p>
      <p><strong>Due Date:</strong> ${formattedDate}</p>
    </div>
    <p>Please ensure payment is made on or before the due date to avoid late fees.</p>
    <p>You can make your payment through M-Pesa or via your tenant portal.</p>
    <div class="footer">
      <p>&copy; ${new Date().getFullYear()} Starmax Ltd. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
    `;
    }
    getOverdueNoticeTemplate(firstName, amount, daysOverdue, propertyName) {
        const formattedAmount = amount.toLocaleString('en-US', {
            style: 'currency',
            currency: 'KES',
        });
        return `
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .alert-box { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; }
    .amount { font-size: 24px; font-weight: bold; color: #dc3545; }
    .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Overdue Payment Notice</h2>
    <p>Hi ${firstName},</p>
    <p>This is an urgent notice regarding your overdue rent payment.</p>
    <div class="alert-box">
      <p><strong>Property:</strong> ${propertyName}</p>
      <p><strong>Amount Overdue:</strong> <span class="amount">${formattedAmount}</span></p>
      <p><strong>Days Overdue:</strong> ${daysOverdue} days</p>
    </div>
    <p><strong>Please make payment immediately to avoid further action.</strong></p>
    <p>If you've already paid, please disregard this notice. For payment arrangements or questions, contact us immediately.</p>
    <div class="footer">
      <p>&copy; ${new Date().getFullYear()} Starmax Ltd. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
    `;
    }
    getMaintenanceUpdateTemplate(firstName, requestId, status, description) {
        return `
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .status-box { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; }
    .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Maintenance Request Update</h2>
    <p>Hi ${firstName},</p>
    <p>Your maintenance request has been updated.</p>
    <div class="status-box">
      <p><strong>Request ID:</strong> #${requestId}</p>
      <p><strong>Status:</strong> ${status}</p>
      <p><strong>Update:</strong> ${description}</p>
    </div>
    <p>Thank you for your patience. We'll keep you informed of any further updates.</p>
    <div class="footer">
      <p>&copy; ${new Date().getFullYear()} Starmax Ltd. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
    `;
    }
};
exports.EmailService = EmailService;
exports.EmailService = EmailService = EmailService_1 = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [config_1.ConfigService])
], EmailService);
//# sourceMappingURL=email.service.js.map
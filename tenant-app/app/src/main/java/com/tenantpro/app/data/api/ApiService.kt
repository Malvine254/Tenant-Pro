package com.tenantpro.app.data.api

import com.tenantpro.app.data.model.AuthResponse
import com.tenantpro.app.data.model.RegisterResponse
import com.tenantpro.app.data.model.EmailLoginRequest
import com.tenantpro.app.data.model.ForgotPasswordRequest
import com.tenantpro.app.data.model.InitiatePaymentRequest
import com.tenantpro.app.data.model.InitiatePaymentResponse
import com.google.gson.JsonElement
import com.tenantpro.app.data.model.AcceptInvitationRequest
import com.tenantpro.app.data.model.CreateMaintenanceRequest
import com.tenantpro.app.data.model.MaintenanceRequestItem
import com.tenantpro.app.data.model.MessageResponse
import com.tenantpro.app.data.model.NotificationItem
import com.tenantpro.app.data.model.Payment
import com.tenantpro.app.data.model.RegisterRequest
import com.tenantpro.app.data.model.RequestEmailOtpRequest
import com.tenantpro.app.data.model.RequestOtpRequest
import com.tenantpro.app.data.model.ResetPasswordRequest
import com.tenantpro.app.data.model.SupportMessageDto
import com.tenantpro.app.data.model.SupportMessageRequest
import com.tenantpro.app.data.model.UploadAttachmentResponse
import com.tenantpro.app.data.model.UpdateProfileRequest
import com.tenantpro.app.data.model.UserProfile
import com.tenantpro.app.data.model.VerifyEmailOtpRequest
import com.tenantpro.app.data.model.VerifyOtpRequest
import okhttp3.MultipartBody
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Part
import retrofit2.http.Path
import retrofit2.http.Query

interface ApiService {

    // ──────────────────────────────────────────────────────────────────────────
    // Auth
    // ──────────────────────────────────────────────────────────────────────────

    /** POST /auth/login  — email/password login, returns JWT */
    @POST("auth/login")
    suspend fun loginWithEmail(@Body body: EmailLoginRequest): Response<AuthResponse>

    /** POST /auth/register  — register new user, sends verification email (no JWT yet) */
    @POST("auth/register")
    suspend fun registerUser(@Body body: RegisterRequest): Response<RegisterResponse>

    /** POST /auth/otp/request  — sends OTP to the tenant's phone */
    @POST("auth/otp/request")
    suspend fun requestOtp(@Body body: RequestOtpRequest): Response<MessageResponse>

    /** POST /auth/otp/verify  — validates OTP, returns JWT */
    @POST("auth/otp/verify")
    suspend fun verifyOtp(@Body body: VerifyOtpRequest): Response<AuthResponse>

    /** POST /auth/email-otp/request  — sends OTP to the tenant's email */
    @POST("auth/email-otp/request")
    suspend fun requestEmailOtp(@Body body: RequestEmailOtpRequest): Response<MessageResponse>

    /** POST /auth/email-otp/verify  — validates email OTP, returns JWT */
    @POST("auth/email-otp/verify")
    suspend fun verifyEmailOtp(@Body body: VerifyEmailOtpRequest): Response<AuthResponse>

    /** POST /auth/forgot-password  — sends password reset OTP to email */
    @POST("auth/forgot-password")
    suspend fun forgotPassword(@Body body: ForgotPasswordRequest): Response<MessageResponse>

    /** POST /auth/reset-password  — resets password with OTP */
    @POST("auth/reset-password")
    suspend fun resetPassword(@Body body: ResetPasswordRequest): Response<MessageResponse>

    /** GET /auth/me  — returns the currently authenticated user */
    @GET("auth/me")
    suspend fun getMe(): Response<UserProfile>

    /** GET /users/me/profile — richer tenant profile + tenancy details */
    @GET("users/me/profile")
    suspend fun getMyProfile(): Response<UserProfile>

    /** PATCH /users/me/profile — update profile on backend */
    @PATCH("users/me/profile")
    suspend fun updateMyProfile(@Body body: UpdateProfileRequest): Response<UserProfile>

    @Multipart
    @POST("users/me/profile-image")
    suspend fun uploadMyProfileImage(@Part file: MultipartBody.Part): Response<UserProfile>

    /** POST /invitations/accept — link current tenant account to an invited unit */
    @POST("invitations/accept")
    suspend fun acceptInvitation(@Body body: AcceptInvitationRequest): Response<MessageResponse>

    @POST("invitations/claim")
    suspend fun claimMatchingInvitations(): Response<MessageResponse>

    /** POST /users/device-token — save FCM token for push notifications */
    @POST("users/device-token")
    suspend fun saveDeviceToken(@Body body: Map<String, String>): Response<MessageResponse>

    // ──────────────────────────────────────────────────────────────────────────
    // Invoices
    // ──────────────────────────────────────────────────────────────────────────

    /** GET /invoices  — returns invoices for the authenticated tenant */
    @GET("invoices")
    suspend fun getInvoices(@Query("per_page") perPage: Int = 100): Response<JsonElement>

    // ──────────────────────────────────────────────────────────────────────────
    // Payments
    // ──────────────────────────────────────────────────────────────────────────

    /** POST /payments/pay  — triggers M-Pesa STK Push */
    @POST("payments/pay")
    suspend fun initiatePayment(
        @Body body: InitiatePaymentRequest
    ): Response<InitiatePaymentResponse>

    @POST("payments/manual-instructions")
    suspend fun getManualPaymentInstructions(
        @Body body: com.tenantpro.app.data.model.ManualPaymentInstructionsRequest
    ): Response<com.tenantpro.app.data.model.ManualPaymentInstructions>

    /** GET /payments/invoice/{invoiceId}  — payment history for one invoice */
    @GET("payments")
    suspend fun getPayments(@Query("per_page") perPage: Int = 100): Response<JsonElement>

    @GET("payments/invoice/{invoiceId}")
    suspend fun getPaymentsByInvoice(
        @Path("invoiceId") invoiceId: String
    ): Response<List<Payment>>

    // ──────────────────────────────────────────────────────────────────────────
    // Notifications
    // ──────────────────────────────────────────────────────────────────────────

    @GET("notifications")
    suspend fun getNotifications(): Response<List<NotificationItem>>

    @PATCH("notifications/{id}/read")
    suspend fun markNotificationRead(@Path("id") id: String): Response<NotificationItem>

    @POST("notifications/mark-all-read")
    suspend fun markAllNotificationsRead(): Response<MessageResponse>

    // ──────────────────────────────────────────────────────────────────────────
    // Support / Queries
    // ──────────────────────────────────────────────────────────────────────────

    @GET("support/messages")
    suspend fun getSupportMessages(): Response<List<SupportMessageDto>>

    @POST("support/messages")
    suspend fun sendSupportMessage(@Body body: SupportMessageRequest): Response<List<SupportMessageDto>>

    @Multipart
    @POST("support/upload")
    suspend fun uploadSupportAttachment(@Part file: MultipartBody.Part): Response<UploadAttachmentResponse>

    @POST("support/heartbeat")
    suspend fun supportHeartbeat(): Response<Map<String, Boolean>>

    @POST("support/typing")
    suspend fun setSupportTyping(@Body body: Map<String, Boolean>): Response<Map<String, Boolean>>

    // ──────────────────────────────────────────────────────────────────────────
    // Maintenance
    // ──────────────────────────────────────────────────────────────────────────

    @GET("maintenance")
    suspend fun getMaintenanceRequests(): Response<List<MaintenanceRequestItem>>

    @POST("maintenance")
    suspend fun createMaintenanceRequest(@Body body: CreateMaintenanceRequest): Response<MaintenanceRequestItem>
}

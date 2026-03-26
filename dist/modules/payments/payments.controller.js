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
var __param = (this && this.__param) || function (paramIndex, decorator) {
    return function (target, key) { decorator(target, key, paramIndex); }
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.PaymentsController = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const roles_decorator_1 = require("../../common/decorators/roles.decorator");
const jwt_auth_guard_1 = require("../../common/guards/jwt-auth.guard");
const roles_guard_1 = require("../../common/guards/roles.guard");
const mpesa_callback_dto_1 = require("./dto/mpesa-callback.dto");
const initiate_payment_dto_1 = require("./dto/initiate-payment.dto");
const payments_service_1 = require("./payments.service");
let PaymentsController = class PaymentsController {
    constructor(paymentsService) {
        this.paymentsService = paymentsService;
    }
    initiatePayment(req, dto) {
        return this.paymentsService.initiateStkPush(req.user.userId, req.user.role, dto);
    }
    mpesaCallback(body) {
        return this.paymentsService.handleCallback(body);
    }
    getPaymentsByInvoice(req, invoiceId) {
        return this.paymentsService.getPaymentsByInvoice(req.user.userId, req.user.role, invoiceId);
    }
};
exports.PaymentsController = PaymentsController;
__decorate([
    (0, common_1.Post)('pay'),
    (0, common_1.UseGuards)(jwt_auth_guard_1.JwtAuthGuard, roles_guard_1.RolesGuard),
    (0, roles_decorator_1.Roles)(client_1.RoleName.TENANT, client_1.RoleName.LANDLORD, client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, initiate_payment_dto_1.InitiatePaymentDto]),
    __metadata("design:returntype", void 0)
], PaymentsController.prototype, "initiatePayment", null);
__decorate([
    (0, common_1.Post)('mpesa/callback'),
    __param(0, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [mpesa_callback_dto_1.MpesaCallbackDto]),
    __metadata("design:returntype", void 0)
], PaymentsController.prototype, "mpesaCallback", null);
__decorate([
    (0, common_1.Get)('invoice/:invoiceId'),
    (0, common_1.UseGuards)(jwt_auth_guard_1.JwtAuthGuard, roles_guard_1.RolesGuard),
    (0, roles_decorator_1.Roles)(client_1.RoleName.TENANT, client_1.RoleName.LANDLORD, client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Param)('invoiceId')),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, String]),
    __metadata("design:returntype", void 0)
], PaymentsController.prototype, "getPaymentsByInvoice", null);
exports.PaymentsController = PaymentsController = __decorate([
    (0, common_1.Controller)('payments'),
    __metadata("design:paramtypes", [payments_service_1.PaymentsService])
], PaymentsController);
//# sourceMappingURL=payments.controller.js.map
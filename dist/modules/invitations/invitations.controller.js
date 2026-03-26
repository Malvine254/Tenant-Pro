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
exports.InvitationsController = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const roles_decorator_1 = require("../../common/decorators/roles.decorator");
const jwt_auth_guard_1 = require("../../common/guards/jwt-auth.guard");
const roles_guard_1 = require("../../common/guards/roles.guard");
const accept_invitation_dto_1 = require("./dto/accept-invitation.dto");
const create_invitation_dto_1 = require("./dto/create-invitation.dto");
const invitations_service_1 = require("./invitations.service");
let InvitationsController = class InvitationsController {
    constructor(invitationsService) {
        this.invitationsService = invitationsService;
    }
    create(req, dto) {
        return this.invitationsService.createInvitation(req.user.userId, dto);
    }
    accept(req, dto) {
        return this.invitationsService.acceptInvitation(req.user.userId, dto);
    }
    expire(req) {
        return this.invitationsService.expirePendingInvitations(req.user.userId, req.user.role);
    }
};
exports.InvitationsController = InvitationsController;
__decorate([
    (0, common_1.Post)(),
    (0, roles_decorator_1.Roles)(client_1.RoleName.LANDLORD),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, create_invitation_dto_1.CreateInvitationDto]),
    __metadata("design:returntype", void 0)
], InvitationsController.prototype, "create", null);
__decorate([
    (0, common_1.Post)('accept'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.TENANT),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, accept_invitation_dto_1.AcceptInvitationDto]),
    __metadata("design:returntype", void 0)
], InvitationsController.prototype, "accept", null);
__decorate([
    (0, common_1.Post)('expire'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.LANDLORD, client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Req)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object]),
    __metadata("design:returntype", void 0)
], InvitationsController.prototype, "expire", null);
exports.InvitationsController = InvitationsController = __decorate([
    (0, common_1.Controller)('invitations'),
    (0, common_1.UseGuards)(jwt_auth_guard_1.JwtAuthGuard, roles_guard_1.RolesGuard),
    __metadata("design:paramtypes", [invitations_service_1.InvitationsService])
], InvitationsController);
//# sourceMappingURL=invitations.controller.js.map
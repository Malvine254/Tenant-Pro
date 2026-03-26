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
exports.MaintenanceController = void 0;
const common_1 = require("@nestjs/common");
const jwt_auth_guard_1 = require("../../common/guards/jwt-auth.guard");
const roles_guard_1 = require("../../common/guards/roles.guard");
const roles_decorator_1 = require("../../common/decorators/roles.decorator");
const client_1 = require("@prisma/client");
const maintenance_service_1 = require("./maintenance.service");
const create_request_dto_1 = require("./dto/create-request.dto");
const assign_caretaker_dto_1 = require("./dto/assign-caretaker.dto");
const update_status_dto_1 = require("./dto/update-status.dto");
const request_id_param_dto_1 = require("./dto/request-id-param.dto");
let MaintenanceController = class MaintenanceController {
    constructor(maintenanceService) {
        this.maintenanceService = maintenanceService;
    }
    createRequest(req, dto) {
        return this.maintenanceService.createRequest(req.user.userId, dto);
    }
    listRequests(req) {
        return this.maintenanceService.listRequests(req.user.userId, req.user.role);
    }
    getRequest(req, params) {
        return this.maintenanceService.getRequest(req.user.userId, req.user.role, params.id);
    }
    assignCaretaker(req, params, dto) {
        return this.maintenanceService.assignCaretaker(req.user.userId, params.id, dto);
    }
    updateStatus(req, params, dto) {
        return this.maintenanceService.updateStatus(req.user.userId, req.user.role, params.id, dto);
    }
};
exports.MaintenanceController = MaintenanceController;
__decorate([
    (0, common_1.Post)(),
    (0, roles_decorator_1.Roles)(client_1.RoleName.TENANT),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, create_request_dto_1.CreateRequestDto]),
    __metadata("design:returntype", void 0)
], MaintenanceController.prototype, "createRequest", null);
__decorate([
    (0, common_1.Get)(),
    (0, roles_decorator_1.Roles)(client_1.RoleName.TENANT, client_1.RoleName.CARETAKER, client_1.RoleName.LANDLORD, client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Req)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object]),
    __metadata("design:returntype", void 0)
], MaintenanceController.prototype, "listRequests", null);
__decorate([
    (0, common_1.Get)(':id'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.TENANT, client_1.RoleName.CARETAKER, client_1.RoleName.LANDLORD, client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Param)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, request_id_param_dto_1.RequestIdParamDto]),
    __metadata("design:returntype", void 0)
], MaintenanceController.prototype, "getRequest", null);
__decorate([
    (0, common_1.Patch)(':id/assign'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.LANDLORD),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Param)()),
    __param(2, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, request_id_param_dto_1.RequestIdParamDto,
        assign_caretaker_dto_1.AssignCaretakerDto]),
    __metadata("design:returntype", void 0)
], MaintenanceController.prototype, "assignCaretaker", null);
__decorate([
    (0, common_1.Patch)(':id/status'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.LANDLORD, client_1.RoleName.CARETAKER, client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Param)()),
    __param(2, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, request_id_param_dto_1.RequestIdParamDto,
        update_status_dto_1.UpdateStatusDto]),
    __metadata("design:returntype", void 0)
], MaintenanceController.prototype, "updateStatus", null);
exports.MaintenanceController = MaintenanceController = __decorate([
    (0, common_1.UseGuards)(jwt_auth_guard_1.JwtAuthGuard, roles_guard_1.RolesGuard),
    (0, common_1.Controller)('maintenance'),
    __metadata("design:paramtypes", [maintenance_service_1.MaintenanceService])
], MaintenanceController);
//# sourceMappingURL=maintenance.controller.js.map
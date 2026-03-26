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
exports.UsersController = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const roles_decorator_1 = require("../../common/decorators/roles.decorator");
const jwt_auth_guard_1 = require("../../common/guards/jwt-auth.guard");
const roles_guard_1 = require("../../common/guards/roles.guard");
const assign_role_dto_1 = require("./dto/assign-role.dto");
const create_user_dto_1 = require("./dto/create-user.dto");
const update_profile_dto_1 = require("./dto/update-profile.dto");
const update_user_dto_1 = require("./dto/update-user.dto");
const user_id_param_dto_1 = require("./dto/user-id-param.dto");
const users_service_1 = require("./users.service");
let UsersController = class UsersController {
    constructor(usersService) {
        this.usersService = usersService;
    }
    getMyProfile(req) {
        return this.usersService.getProfile(req.user.userId);
    }
    updateMyProfile(req, dto) {
        return this.usersService.updateProfile(req.user.userId, dto);
    }
    create(dto) {
        return this.usersService.create(dto);
    }
    findAll() {
        return this.usersService.findAll();
    }
    findOne(params) {
        return this.usersService.findOne(params.id);
    }
    update(params, dto) {
        return this.usersService.update(params.id, dto);
    }
    assignRole(params, dto) {
        return this.usersService.assignRole(params.id, dto);
    }
    remove(params) {
        return this.usersService.remove(params.id);
    }
};
exports.UsersController = UsersController;
__decorate([
    (0, common_1.Get)('me/profile'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.LANDLORD, client_1.RoleName.TENANT, client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Req)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object]),
    __metadata("design:returntype", void 0)
], UsersController.prototype, "getMyProfile", null);
__decorate([
    (0, common_1.Patch)('me/profile'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.LANDLORD, client_1.RoleName.TENANT, client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, update_profile_dto_1.UpdateProfileDto]),
    __metadata("design:returntype", void 0)
], UsersController.prototype, "updateMyProfile", null);
__decorate([
    (0, common_1.Post)(),
    (0, roles_decorator_1.Roles)(client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [create_user_dto_1.CreateUserDto]),
    __metadata("design:returntype", void 0)
], UsersController.prototype, "create", null);
__decorate([
    (0, common_1.Get)(),
    (0, roles_decorator_1.Roles)(client_1.RoleName.ADMIN),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", []),
    __metadata("design:returntype", void 0)
], UsersController.prototype, "findAll", null);
__decorate([
    (0, common_1.Get)(':id'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Param)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [user_id_param_dto_1.UserIdParamDto]),
    __metadata("design:returntype", void 0)
], UsersController.prototype, "findOne", null);
__decorate([
    (0, common_1.Patch)(':id'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Param)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [user_id_param_dto_1.UserIdParamDto, update_user_dto_1.UpdateUserDto]),
    __metadata("design:returntype", void 0)
], UsersController.prototype, "update", null);
__decorate([
    (0, common_1.Patch)(':id/role'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Param)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [user_id_param_dto_1.UserIdParamDto, assign_role_dto_1.AssignRoleDto]),
    __metadata("design:returntype", void 0)
], UsersController.prototype, "assignRole", null);
__decorate([
    (0, common_1.Delete)(':id'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.ADMIN),
    __param(0, (0, common_1.Param)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [user_id_param_dto_1.UserIdParamDto]),
    __metadata("design:returntype", void 0)
], UsersController.prototype, "remove", null);
exports.UsersController = UsersController = __decorate([
    (0, common_1.Controller)('users'),
    (0, common_1.UseGuards)(jwt_auth_guard_1.JwtAuthGuard, roles_guard_1.RolesGuard),
    __metadata("design:paramtypes", [users_service_1.UsersService])
], UsersController);
//# sourceMappingURL=users.controller.js.map
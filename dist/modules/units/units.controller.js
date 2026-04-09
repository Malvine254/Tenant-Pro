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
exports.UnitsController = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const roles_decorator_1 = require("../../common/decorators/roles.decorator");
const jwt_auth_guard_1 = require("../../common/guards/jwt-auth.guard");
const roles_guard_1 = require("../../common/guards/roles.guard");
const create_unit_dto_1 = require("../properties/dto/create-unit.dto");
const property_id_param_dto_1 = require("../properties/dto/property-id-param.dto");
const properties_service_1 = require("../properties/properties.service");
let UnitsController = class UnitsController {
    constructor(propertiesService) {
        this.propertiesService = propertiesService;
    }
    createUnitForProperty(req, params, dto) {
        return this.propertiesService.addUnit(req.user.userId, req.user.role, params.propertyId, dto);
    }
};
exports.UnitsController = UnitsController;
__decorate([
    (0, common_1.Post)('property/:propertyId'),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Param)()),
    __param(2, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, property_id_param_dto_1.PropertyIdParamDto,
        create_unit_dto_1.CreateUnitDto]),
    __metadata("design:returntype", void 0)
], UnitsController.prototype, "createUnitForProperty", null);
exports.UnitsController = UnitsController = __decorate([
    (0, common_1.Controller)('units'),
    (0, common_1.UseGuards)(jwt_auth_guard_1.JwtAuthGuard, roles_guard_1.RolesGuard),
    (0, roles_decorator_1.Roles)(client_1.RoleName.LANDLORD, client_1.RoleName.ADMIN),
    __metadata("design:paramtypes", [properties_service_1.PropertiesService])
], UnitsController);
//# sourceMappingURL=units.controller.js.map
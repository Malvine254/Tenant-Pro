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
Object.defineProperty(exports, "__esModule", { value: true });
exports.TenantIdParamDto = void 0;
const class_validator_1 = require("class-validator");
class TenantIdParamDto {
}
exports.TenantIdParamDto = TenantIdParamDto;
__decorate([
    (0, class_validator_1.IsUUID)(),
    __metadata("design:type", String)
], TenantIdParamDto.prototype, "tenantId", void 0);
//# sourceMappingURL=tenant-id-param.dto.js.map
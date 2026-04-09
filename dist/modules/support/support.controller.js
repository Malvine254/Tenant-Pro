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
exports.SupportController = void 0;
const common_1 = require("@nestjs/common");
const platform_express_1 = require("@nestjs/platform-express");
const client_1 = require("@prisma/client");
const fs_1 = require("fs");
const path_1 = require("path");
const { diskStorage } = require('multer');
const roles_decorator_1 = require("../../common/decorators/roles.decorator");
const jwt_auth_guard_1 = require("../../common/guards/jwt-auth.guard");
const roles_guard_1 = require("../../common/guards/roles.guard");
const send_support_message_dto_1 = require("./dto/send-support-message.dto");
const support_service_1 = require("./support.service");
const uploadDir = (0, path_1.join)(process.cwd(), 'uploads', 'support');
if (!(0, fs_1.existsSync)(uploadDir)) {
    (0, fs_1.mkdirSync)(uploadDir, { recursive: true });
}
let SupportController = class SupportController {
    constructor(supportService) {
        this.supportService = supportService;
    }
    uploadAttachment(file) {
        if (!file) {
            throw new common_1.BadRequestException('No file uploaded');
        }
        return {
            fileName: file.originalname,
            attachmentName: file.originalname,
            attachmentUri: `/uploads/support/${file.filename}`,
            size: file.size,
            mimeType: file.mimetype,
        };
    }
    listMessages(req) {
        return this.supportService.listMessages(req.user.userId);
    }
    sendMessage(req, dto) {
        return this.supportService.sendMessage(req.user.userId, dto);
    }
    listConversations() {
        return this.supportService.listConversationsForOps();
    }
    startConversation(req, dto) {
        return this.supportService.startConversationForOps(req.user.userId, dto);
    }
    getConversationMessages(conversationId) {
        return this.supportService.getConversationMessages(conversationId);
    }
    replyToConversation(req, conversationId, dto) {
        return this.supportService.replyToConversation(req.user.userId, conversationId, dto);
    }
};
exports.SupportController = SupportController;
__decorate([
    (0, common_1.Post)('upload'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.TENANT, client_1.RoleName.LANDLORD, client_1.RoleName.ADMIN, client_1.RoleName.CARETAKER),
    (0, common_1.UseInterceptors)((0, platform_express_1.FileInterceptor)('file', {
        storage: diskStorage({
            destination: (_req, _file, cb) => cb(null, uploadDir),
            filename: (_req, file, cb) => {
                const safeBase = file.originalname.replace(/[^a-zA-Z0-9._-]/g, '_').replace(/\.[^.]+$/, '');
                const unique = `${Date.now()}-${Math.round(Math.random() * 1e9)}`;
                cb(null, `${safeBase || 'attachment'}-${unique}${(0, path_1.extname)(file.originalname)}`);
            },
        }),
        limits: { fileSize: 10 * 1024 * 1024 },
    })),
    __param(0, (0, common_1.UploadedFile)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object]),
    __metadata("design:returntype", void 0)
], SupportController.prototype, "uploadAttachment", null);
__decorate([
    (0, common_1.Get)('messages'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.TENANT, client_1.RoleName.LANDLORD, client_1.RoleName.ADMIN, client_1.RoleName.CARETAKER),
    __param(0, (0, common_1.Req)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object]),
    __metadata("design:returntype", void 0)
], SupportController.prototype, "listMessages", null);
__decorate([
    (0, common_1.Post)('messages'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.TENANT, client_1.RoleName.LANDLORD, client_1.RoleName.ADMIN, client_1.RoleName.CARETAKER),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, send_support_message_dto_1.SendSupportMessageDto]),
    __metadata("design:returntype", void 0)
], SupportController.prototype, "sendMessage", null);
__decorate([
    (0, common_1.Get)('conversations'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.ADMIN, client_1.RoleName.LANDLORD, client_1.RoleName.CARETAKER),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", []),
    __metadata("design:returntype", void 0)
], SupportController.prototype, "listConversations", null);
__decorate([
    (0, common_1.Post)('conversations'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.ADMIN, client_1.RoleName.LANDLORD, client_1.RoleName.CARETAKER),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, send_support_message_dto_1.SendSupportMessageDto]),
    __metadata("design:returntype", void 0)
], SupportController.prototype, "startConversation", null);
__decorate([
    (0, common_1.Get)('conversations/:conversationId/messages'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.ADMIN, client_1.RoleName.LANDLORD, client_1.RoleName.CARETAKER),
    __param(0, (0, common_1.Param)('conversationId')),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [String]),
    __metadata("design:returntype", void 0)
], SupportController.prototype, "getConversationMessages", null);
__decorate([
    (0, common_1.Post)('conversations/:conversationId/messages'),
    (0, roles_decorator_1.Roles)(client_1.RoleName.ADMIN, client_1.RoleName.LANDLORD, client_1.RoleName.CARETAKER),
    __param(0, (0, common_1.Req)()),
    __param(1, (0, common_1.Param)('conversationId')),
    __param(2, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object, String, send_support_message_dto_1.SendSupportMessageDto]),
    __metadata("design:returntype", void 0)
], SupportController.prototype, "replyToConversation", null);
exports.SupportController = SupportController = __decorate([
    (0, common_1.Controller)('support'),
    (0, common_1.UseGuards)(jwt_auth_guard_1.JwtAuthGuard, roles_guard_1.RolesGuard),
    __metadata("design:paramtypes", [support_service_1.SupportService])
], SupportController);
//# sourceMappingURL=support.controller.js.map
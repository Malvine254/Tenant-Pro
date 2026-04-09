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
exports.MaintenanceService = void 0;
const common_1 = require("@nestjs/common");
const client_1 = require("@prisma/client");
const prisma_service_1 = require("../../prisma/prisma.service");
const notifications_service_1 = require("../notifications/notifications.service");
const ALLOWED_TRANSITIONS = {
    [client_1.MaintenanceStatus.OPEN]: [client_1.MaintenanceStatus.IN_PROGRESS, client_1.MaintenanceStatus.CLOSED],
    [client_1.MaintenanceStatus.IN_PROGRESS]: [client_1.MaintenanceStatus.RESOLVED, client_1.MaintenanceStatus.CLOSED],
    [client_1.MaintenanceStatus.RESOLVED]: [client_1.MaintenanceStatus.CLOSED],
    [client_1.MaintenanceStatus.CLOSED]: [],
};
const STATUS_ROLE_GATE = {
    [client_1.MaintenanceStatus.IN_PROGRESS]: [client_1.RoleName.LANDLORD, client_1.RoleName.CARETAKER, client_1.RoleName.ADMIN],
    [client_1.MaintenanceStatus.RESOLVED]: [client_1.RoleName.LANDLORD, client_1.RoleName.CARETAKER, client_1.RoleName.ADMIN],
    [client_1.MaintenanceStatus.CLOSED]: [client_1.RoleName.LANDLORD, client_1.RoleName.ADMIN],
};
const REQUEST_INCLUDE = {
    unit: { include: { property: { select: { id: true, landlordId: true, name: true } } } },
    tenant: { include: { user: { select: { id: true, firstName: true, lastName: true, phoneNumber: true } } } },
    assignedTo: { select: { id: true, firstName: true, lastName: true, phoneNumber: true } },
    reportedBy: { select: { id: true, firstName: true, lastName: true } },
};
let MaintenanceService = class MaintenanceService {
    constructor(prisma, notificationsService) {
        this.prisma = prisma;
        this.notificationsService = notificationsService;
    }
    async createRequest(actorUserId, dto) {
        const tenant = await this.prisma.tenant.findFirst({
            where: { userId: actorUserId, isActive: true },
        });
        if (!tenant) {
            throw new common_1.ForbiddenException('Only tenants with an active tenancy can submit maintenance requests');
        }
        const created = await this.prisma.maintenanceRequest.create({
            data: {
                tenantId: tenant.id,
                unitId: tenant.unitId,
                reportedById: actorUserId,
                title: dto.title,
                description: dto.description,
                priority: dto.priority ?? client_1.MaintenancePriority.MEDIUM,
                status: client_1.MaintenanceStatus.OPEN,
            },
            include: REQUEST_INCLUDE,
        });
        await this.notificationsService.createNotification(actorUserId, client_1.NotificationType.MAINTENANCE, 'Maintenance request submitted', `${dto.title} has been submitted successfully and is now open.`, { requestId: created.id, status: created.status });
        return created;
    }
    async listRequests(actorUserId, actorRole) {
        if (actorRole === client_1.RoleName.TENANT) {
            const tenant = await this.prisma.tenant.findFirst({ where: { userId: actorUserId, isActive: true } });
            if (!tenant)
                return [];
            return this.prisma.maintenanceRequest.findMany({
                where: { tenantId: tenant.id },
                include: REQUEST_INCLUDE,
                orderBy: { createdAt: 'desc' },
            });
        }
        if (actorRole === client_1.RoleName.CARETAKER) {
            return this.prisma.maintenanceRequest.findMany({
                where: { assignedToId: actorUserId },
                include: REQUEST_INCLUDE,
                orderBy: { createdAt: 'desc' },
            });
        }
        if (actorRole === client_1.RoleName.LANDLORD) {
            const propertyIds = await this.prisma.property
                .findMany({ where: { landlordId: actorUserId }, select: { id: true } })
                .then((ps) => ps.map((p) => p.id));
            return this.prisma.maintenanceRequest.findMany({
                where: { unit: { propertyId: { in: propertyIds } } },
                include: REQUEST_INCLUDE,
                orderBy: { createdAt: 'desc' },
            });
        }
        return this.prisma.maintenanceRequest.findMany({
            include: REQUEST_INCLUDE,
            orderBy: { createdAt: 'desc' },
        });
    }
    async assignCaretaker(actorUserId, requestId, dto) {
        const request = await this.prisma.maintenanceRequest.findUnique({
            where: { id: requestId },
            include: { unit: { include: { property: true } } },
        });
        if (!request)
            throw new common_1.NotFoundException('Maintenance request not found');
        if (request.unit.property.landlordId !== actorUserId) {
            throw new common_1.ForbiddenException('You can only manage requests for your own properties');
        }
        if (request.status === client_1.MaintenanceStatus.CLOSED) {
            throw new common_1.BadRequestException('Cannot assign a caretaker to a closed request');
        }
        const caretaker = await this.prisma.user.findUnique({
            where: { id: dto.caretakerId },
            include: { role: true },
        });
        if (!caretaker)
            throw new common_1.NotFoundException('Caretaker user not found');
        if (caretaker.role.name !== client_1.RoleName.CARETAKER) {
            throw new common_1.BadRequestException(`User ${dto.caretakerId} does not have the CARETAKER role`);
        }
        return this.prisma.maintenanceRequest.update({
            where: { id: requestId },
            data: { assignedToId: dto.caretakerId },
            include: REQUEST_INCLUDE,
        });
    }
    async updateStatus(actorUserId, actorRole, requestId, dto) {
        const request = await this.prisma.maintenanceRequest.findUnique({
            where: { id: requestId },
            include: { unit: { include: { property: true } } },
        });
        if (!request)
            throw new common_1.NotFoundException('Maintenance request not found');
        if (actorRole === client_1.RoleName.TENANT) {
            throw new common_1.ForbiddenException('Tenants cannot update maintenance request status');
        }
        if (actorRole === client_1.RoleName.LANDLORD) {
            if (request.unit.property.landlordId !== actorUserId) {
                throw new common_1.ForbiddenException('You can only update requests for your own properties');
            }
        }
        if (actorRole === client_1.RoleName.CARETAKER) {
            if (request.assignedToId !== actorUserId) {
                throw new common_1.ForbiddenException('You can only update requests assigned to you');
            }
        }
        const allowed = ALLOWED_TRANSITIONS[request.status];
        if (!allowed.includes(dto.status)) {
            throw new common_1.BadRequestException(`Cannot transition from ${request.status} to ${dto.status}. ` +
                `Allowed: ${allowed.length ? allowed.join(', ') : 'none (terminal state)'}`);
        }
        const allowedRoles = STATUS_ROLE_GATE[dto.status];
        if (allowedRoles && !allowedRoles.includes(actorRole)) {
            throw new common_1.ForbiddenException(`Role ${actorRole} cannot set status to ${dto.status}`);
        }
        const updated = await this.prisma.maintenanceRequest.update({
            where: { id: requestId },
            data: {
                status: dto.status,
                resolvedAt: dto.status === client_1.MaintenanceStatus.RESOLVED ? new Date() : undefined,
            },
            include: REQUEST_INCLUDE,
        });
        await this.notificationsService.createNotification(updated.reportedById, client_1.NotificationType.MAINTENANCE, 'Maintenance status updated', `${updated.title} is now ${dto.status.replace('_', ' ').toLowerCase()}.`, { requestId: updated.id, status: updated.status });
        return updated;
    }
    async getRequest(actorUserId, actorRole, requestId) {
        const request = await this.prisma.maintenanceRequest.findUnique({
            where: { id: requestId },
            include: REQUEST_INCLUDE,
        });
        if (!request)
            throw new common_1.NotFoundException('Maintenance request not found');
        if (actorRole === client_1.RoleName.TENANT && request.reportedById !== actorUserId) {
            throw new common_1.ForbiddenException('Access denied');
        }
        if (actorRole === client_1.RoleName.CARETAKER && request.assignedToId !== actorUserId) {
            throw new common_1.ForbiddenException('Access denied');
        }
        if (actorRole === client_1.RoleName.LANDLORD) {
            const isOwner = request.unit.property?.landlordId === actorUserId;
            if (!isOwner)
                throw new common_1.ForbiddenException('Access denied');
        }
        return request;
    }
};
exports.MaintenanceService = MaintenanceService;
exports.MaintenanceService = MaintenanceService = __decorate([
    (0, common_1.Injectable)(),
    __metadata("design:paramtypes", [prisma_service_1.PrismaService,
        notifications_service_1.NotificationsService])
], MaintenanceService);
//# sourceMappingURL=maintenance.service.js.map
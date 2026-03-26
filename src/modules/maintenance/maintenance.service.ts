import {
  BadRequestException,
  ForbiddenException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { MaintenancePriority, MaintenanceStatus, RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { AssignCaretakerDto } from './dto/assign-caretaker.dto';
import { CreateRequestDto } from './dto/create-request.dto';
import { UpdateStatusDto } from './dto/update-status.dto';

// Valid forward transitions per role
const ALLOWED_TRANSITIONS: Record<MaintenanceStatus, MaintenanceStatus[]> = {
  [MaintenanceStatus.OPEN]:        [MaintenanceStatus.IN_PROGRESS, MaintenanceStatus.CLOSED],
  [MaintenanceStatus.IN_PROGRESS]: [MaintenanceStatus.RESOLVED, MaintenanceStatus.CLOSED],
  [MaintenanceStatus.RESOLVED]:    [MaintenanceStatus.CLOSED],
  [MaintenanceStatus.CLOSED]:      [],
};

// Roles that can drive each target status
const STATUS_ROLE_GATE: Partial<Record<MaintenanceStatus, RoleName[]>> = {
  [MaintenanceStatus.IN_PROGRESS]: [RoleName.LANDLORD, RoleName.CARETAKER, RoleName.ADMIN],
  [MaintenanceStatus.RESOLVED]:    [RoleName.LANDLORD, RoleName.CARETAKER, RoleName.ADMIN],
  [MaintenanceStatus.CLOSED]:      [RoleName.LANDLORD, RoleName.ADMIN],
};

const REQUEST_INCLUDE = {
  unit: { include: { property: { select: { id: true, landlordId: true, name: true } } } },
  tenant: { include: { user: { select: { id: true, firstName: true, lastName: true, phoneNumber: true } } } },
  assignedTo: { select: { id: true, firstName: true, lastName: true, phoneNumber: true } },
  reportedBy: { select: { id: true, firstName: true, lastName: true } },
} as const;

@Injectable()
export class MaintenanceService {
  constructor(private readonly prisma: PrismaService) {}

  // ---------------------------------------------------------------------------
  // Tenant: create a new request
  // ---------------------------------------------------------------------------

  async createRequest(actorUserId: string, dto: CreateRequestDto) {
    const tenant = await this.prisma.tenant.findFirst({
      where: { userId: actorUserId, isActive: true },
    });

    if (!tenant) {
      throw new ForbiddenException('Only tenants with an active tenancy can submit maintenance requests');
    }

    return this.prisma.maintenanceRequest.create({
      data: {
        tenantId:    tenant.id,
        unitId:      tenant.unitId,
        reportedById: actorUserId,
        title:       dto.title,
        description: dto.description,
        priority:    dto.priority ?? MaintenancePriority.MEDIUM,
        status:      MaintenanceStatus.OPEN,
      },
      include: REQUEST_INCLUDE,
    });
  }

  // ---------------------------------------------------------------------------
  // List requests – role-aware
  // ---------------------------------------------------------------------------

  async listRequests(actorUserId: string, actorRole: RoleName) {
    if (actorRole === RoleName.TENANT) {
      const tenant = await this.prisma.tenant.findFirst({ where: { userId: actorUserId, isActive: true } });
      if (!tenant) return [];
      return this.prisma.maintenanceRequest.findMany({
        where: { tenantId: tenant.id },
        include: REQUEST_INCLUDE,
        orderBy: { createdAt: 'desc' },
      });
    }

    if (actorRole === RoleName.CARETAKER) {
      return this.prisma.maintenanceRequest.findMany({
        where: { assignedToId: actorUserId },
        include: REQUEST_INCLUDE,
        orderBy: { createdAt: 'desc' },
      });
    }

    if (actorRole === RoleName.LANDLORD) {
      // Only requests belonging to this landlord's properties
      const propertyIds = await this.prisma.property
        .findMany({ where: { landlordId: actorUserId }, select: { id: true } })
        .then((ps) => ps.map((p) => p.id));

      return this.prisma.maintenanceRequest.findMany({
        where: { unit: { propertyId: { in: propertyIds } } },
        include: REQUEST_INCLUDE,
        orderBy: { createdAt: 'desc' },
      });
    }

    // ADMIN – all requests
    return this.prisma.maintenanceRequest.findMany({
      include: REQUEST_INCLUDE,
      orderBy: { createdAt: 'desc' },
    });
  }

  // ---------------------------------------------------------------------------
  // Landlord: assign a caretaker to a request
  // ---------------------------------------------------------------------------

  async assignCaretaker(actorUserId: string, requestId: string, dto: AssignCaretakerDto) {
    const request = await this.prisma.maintenanceRequest.findUnique({
      where: { id: requestId },
      include: { unit: { include: { property: true } } },
    });

    if (!request) throw new NotFoundException('Maintenance request not found');

    // Verify the request belongs to this landlord's property
    if (request.unit.property.landlordId !== actorUserId) {
      throw new ForbiddenException('You can only manage requests for your own properties');
    }

    if (request.status === MaintenanceStatus.CLOSED) {
      throw new BadRequestException('Cannot assign a caretaker to a closed request');
    }

    // Verify the assignee has the CARETAKER role
    const caretaker = await this.prisma.user.findUnique({
      where: { id: dto.caretakerId },
      include: { role: true },
    });

    if (!caretaker) throw new NotFoundException('Caretaker user not found');
    if (caretaker.role.name !== RoleName.CARETAKER) {
      throw new BadRequestException(`User ${dto.caretakerId} does not have the CARETAKER role`);
    }

    return this.prisma.maintenanceRequest.update({
      where: { id: requestId },
      data: { assignedToId: dto.caretakerId },
      include: REQUEST_INCLUDE,
    });
  }

  // ---------------------------------------------------------------------------
  // Update request status – role + workflow enforced
  // ---------------------------------------------------------------------------

  async updateStatus(actorUserId: string, actorRole: RoleName, requestId: string, dto: UpdateStatusDto) {
    const request = await this.prisma.maintenanceRequest.findUnique({
      where: { id: requestId },
      include: { unit: { include: { property: true } } },
    });

    if (!request) throw new NotFoundException('Maintenance request not found');

    // Access control
    if (actorRole === RoleName.TENANT) {
      throw new ForbiddenException('Tenants cannot update maintenance request status');
    }

    if (actorRole === RoleName.LANDLORD) {
      if (request.unit.property.landlordId !== actorUserId) {
        throw new ForbiddenException('You can only update requests for your own properties');
      }
    }

    if (actorRole === RoleName.CARETAKER) {
      if (request.assignedToId !== actorUserId) {
        throw new ForbiddenException('You can only update requests assigned to you');
      }
    }

    // Transition validity
    const allowed = ALLOWED_TRANSITIONS[request.status];
    if (!allowed.includes(dto.status)) {
      throw new BadRequestException(
        `Cannot transition from ${request.status} to ${dto.status}. ` +
        `Allowed: ${allowed.length ? allowed.join(', ') : 'none (terminal state)'}`,
      );
    }

    // Role gate for target status
    const allowedRoles = STATUS_ROLE_GATE[dto.status];
    if (allowedRoles && !allowedRoles.includes(actorRole)) {
      throw new ForbiddenException(
        `Role ${actorRole} cannot set status to ${dto.status}`,
      );
    }

    return this.prisma.maintenanceRequest.update({
      where: { id: requestId },
      data: {
        status:     dto.status,
        resolvedAt: dto.status === MaintenanceStatus.RESOLVED ? new Date() : undefined,
      },
      include: REQUEST_INCLUDE,
    });
  }

  // ---------------------------------------------------------------------------
  // Get single request
  // ---------------------------------------------------------------------------

  async getRequest(actorUserId: string, actorRole: RoleName, requestId: string) {
    const request = await this.prisma.maintenanceRequest.findUnique({
      where: { id: requestId },
      include: REQUEST_INCLUDE,
    });

    if (!request) throw new NotFoundException('Maintenance request not found');

    if (actorRole === RoleName.TENANT && request.reportedById !== actorUserId) {
      throw new ForbiddenException('Access denied');
    }

    if (actorRole === RoleName.CARETAKER && request.assignedToId !== actorUserId) {
      throw new ForbiddenException('Access denied');
    }

    if (actorRole === RoleName.LANDLORD) {
      const isOwner = (request.unit as any).property?.landlordId === actorUserId;
      if (!isOwner) throw new ForbiddenException('Access denied');
    }

    return request;
  }
}

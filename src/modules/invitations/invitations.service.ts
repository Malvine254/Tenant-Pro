import {
  BadRequestException,
  ForbiddenException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import {
  InvitationStatus,
  RoleName,
  UnitStatus,
} from '@prisma/client';
import { randomBytes } from 'crypto';
import { PrismaService } from '../../prisma/prisma.service';
import { AcceptInvitationDto } from './dto/accept-invitation.dto';
import { CreateInvitationDto } from './dto/create-invitation.dto';

@Injectable()
export class InvitationsService {
  constructor(private readonly prisma: PrismaService) {}

  private async generateUniqueCode() {
    for (let attempt = 0; attempt < 5; attempt += 1) {
      const code = randomBytes(4).toString('hex').toUpperCase();
      const exists = await this.prisma.invitation.findUnique({ where: { code } });
      if (!exists) {
        return code;
      }
    }

    throw new BadRequestException('Unable to generate a unique invitation code');
  }

  async createInvitation(actorUserId: string, actorRole: RoleName, dto: CreateInvitationDto) {
    const property = await this.prisma.property.findUnique({
      where: { id: dto.propertyId },
    });

    if (!property) {
      throw new NotFoundException('Property not found');
    }

    if (actorRole !== RoleName.ADMIN && property.landlordId !== actorUserId) {
      throw new ForbiddenException('You can only invite tenants to your own properties');
    }

    const unit = await this.prisma.unit.findUnique({
      where: { id: dto.unitId },
    });

    if (!unit || unit.propertyId !== dto.propertyId) {
      throw new NotFoundException('Unit not found for the selected property');
    }

    const code = await this.generateUniqueCode();
    const expiryHours = dto.expiresInHours ?? 72;
    const expiresAt = new Date(Date.now() + expiryHours * 60 * 60 * 1000);

    const invitation = await this.prisma.invitation.create({
      data: {
        code,
        propertyId: dto.propertyId,
        unitId: dto.unitId,
        sentById: actorUserId,
        phoneNumber: dto.phoneNumber,
        sentVia: dto.sentVia,
        expiresAt,
        status: InvitationStatus.PENDING,
      },
    });

    return invitation;
  }

  async acceptInvitation(userId: string, dto: AcceptInvitationDto) {
    const invitation = await this.prisma.invitation.findUnique({
      where: { code: dto.code },
      include: {
        property: true,
        unit: true,
      },
    });

    if (!invitation) {
      throw new NotFoundException('Invitation not found');
    }

    if (invitation.status !== InvitationStatus.PENDING) {
      throw new BadRequestException('Invitation is no longer pending');
    }

    if (invitation.expiresAt.getTime() < Date.now()) {
      await this.prisma.invitation.update({
        where: { id: invitation.id },
        data: { status: InvitationStatus.EXPIRED },
      });
      throw new BadRequestException('Invitation has expired');
    }

    const user = await this.prisma.user.findUnique({
      where: { id: userId },
      include: { role: true },
    });

    if (!user) {
      throw new NotFoundException('User not found');
    }

    if (user.role.name !== RoleName.TENANT) {
      throw new ForbiddenException('Only users with TENANT role can accept invitations');
    }

    if (user.phoneNumber !== invitation.phoneNumber) {
      throw new ForbiddenException('Invitation phone number does not match your account');
    }

    const activeTenantOnUnit = await this.prisma.tenant.findFirst({
      where: {
        unitId: invitation.unitId,
        isActive: true,
      },
    });

    if (activeTenantOnUnit) {
      throw new BadRequestException('This unit is already occupied by an active tenant');
    }

    const result = await this.prisma.$transaction(async (tx) => {
      const tenant = await tx.tenant.upsert({
        where: { userId: user.id },
        update: {
          unitId: invitation.unitId,
          isActive: true,
          moveInDate: new Date(),
          moveOutDate: null,
        },
        create: {
          userId: user.id,
          unitId: invitation.unitId,
          isActive: true,
          moveInDate: new Date(),
        },
      });

      await tx.unit.update({
        where: { id: invitation.unitId },
        data: { status: UnitStatus.OCCUPIED },
      });

      const acceptedInvitation = await tx.invitation.update({
        where: { id: invitation.id },
        data: {
          status: InvitationStatus.ACCEPTED,
          acceptedAt: new Date(),
        },
      });

      return { tenant, invitation: acceptedInvitation };
    });

    return result;
  }

  async expirePendingInvitations(actorUserId: string, actorRole: RoleName) {
    const now = new Date();

    const whereClause =
      actorRole === RoleName.ADMIN
        ? {
            status: InvitationStatus.PENDING,
            expiresAt: { lt: now },
          }
        : {
            status: InvitationStatus.PENDING,
            expiresAt: { lt: now },
            property: {
              landlordId: actorUserId,
            },
          };

    const result = await this.prisma.invitation.updateMany({
      where: whereClause,
      data: {
        status: InvitationStatus.EXPIRED,
      },
    });

    return {
      message: 'Expired invitations processed successfully',
      updatedCount: result.count,
    };
  }
}
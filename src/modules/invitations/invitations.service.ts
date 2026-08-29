import {
  BadRequestException,
  ForbiddenException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import {
  InvitationStatus,
  NotificationType,
  RoleName,
  UnitStatus,
} from '@prisma/client';
import { randomBytes } from 'crypto';
import { EmailService } from '../email/email.service';
import { NotificationsService } from '../notifications/notifications.service';
import { PrismaService } from '../../prisma/prisma.service';
import { AcceptInvitationDto } from './dto/accept-invitation.dto';
import { CreateInvitationDto } from './dto/create-invitation.dto';

@Injectable()
export class InvitationsService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly emailService: EmailService,
    private readonly notificationsService: NotificationsService,
  ) {}

  private phoneVariants(phone?: string | null) {
    const digits = phone?.replace(/\D/g, '') ?? '';
    if (!digits) return [];
    const local = digits.startsWith('254') ? `0${digits.slice(3)}` : digits;
    const international = local.startsWith('0') ? `254${local.slice(1)}` : local;
    return [...new Set([phone!.trim(), local, international, `+${international}`])];
  }

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
    const tenantEmail = dto.tenantEmail.trim().toLowerCase();

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

    const expiryHours = dto.expiresInHours ?? 72;
    const expiresAt = new Date(Date.now() + expiryHours * 60 * 60 * 1000);

    const tenantUser = await this.prisma.user.findFirst({
      where: {
        OR: [
          { email: tenantEmail },
          ...(dto.phoneNumber
            ? [{ phoneNumber: { in: this.phoneVariants(dto.phoneNumber) } }]
            : []),
        ],
      },
      select: { id: true, firstName: true, role: { select: { name: true } } },
    });

    // Existing tenant accounts are linked immediately; invite codes are only for new accounts.
    if (tenantUser?.role.name === RoleName.TENANT) {
      const occupiedBy = await this.prisma.tenant.findFirst({
        where: { unitId: dto.unitId, isActive: true, NOT: { userId: tenantUser.id } },
      });
      if (occupiedBy) {
        throw new BadRequestException('This unit is already occupied by another active tenant');
      }

      const assignment = await this.prisma.$transaction(async (tx) => {
        const existing = await tx.tenant.findUnique({
          where: { userId_unitId: { userId: tenantUser.id, unitId: dto.unitId } },
        });
        const tenant = existing
          ? await tx.tenant.update({
              where: { id: existing.id },
              data: { isActive: true, moveInDate: new Date(), moveOutDate: null },
            })
          : await tx.tenant.create({
              data: { userId: tenantUser.id, unitId: dto.unitId, isActive: true, moveInDate: new Date() },
            });
        await tx.unit.update({ where: { id: dto.unitId }, data: { status: UnitStatus.OCCUPIED } });
        return tenant;
      });

      void this.emailService.sendUnitAssignmentEmail(
        tenantEmail,
        tenantUser.firstName ?? undefined,
        property.name,
        unit.unitNumber,
      );
      void this.notificationsService.createNotification(
        tenantUser.id,
        NotificationType.GENERAL,
        'Unit connected',
        `Your account is now connected to Unit ${unit.unitNumber} at ${property.name}.`,
        { unitId: dto.unitId, propertyId: dto.propertyId },
      );
      return { ...assignment, automaticallyAssigned: true };
    }

    const code = await this.generateUniqueCode();

    const invitation = await this.prisma.invitation.create({
      data: {
        code,
        propertyId: dto.propertyId,
        unitId: dto.unitId,
        sentById: actorUserId,
        phoneNumber: dto.phoneNumber ?? '',
        tenantEmail,
        sentVia: dto.sentVia,
        expiresAt,
        status: InvitationStatus.PENDING,
      },
    });

    void this.emailService.sendInvitationEmail(
      tenantEmail,
      code,
      expiresAt,
      property.name,
      unit.unitNumber,
      dto.tenantName,
    );

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

    const invitationEmail = invitation.tenantEmail?.trim().toLowerCase();
    const userEmail = user.email?.trim().toLowerCase();
    if (invitationEmail) {
      if (!userEmail || userEmail !== invitationEmail) {
        throw new ForbiddenException('Invitation email does not match your account');
      }
    } else if (
      invitation.phoneNumber &&
      !this.phoneVariants(invitation.phoneNumber).includes(user.phoneNumber)
    ) {
      throw new ForbiddenException('Invitation phone number does not match your account');
    }

    const activeTenantOnUnit = await this.prisma.tenant.findFirst({
      where: {
        unitId: invitation.unitId,
        isActive: true,
        NOT: { userId: user.id },
      },
    });

    if (activeTenantOnUnit) {
      throw new BadRequestException('This unit is already occupied by another active tenant');
    }

    const result = await this.prisma.$transaction(async (tx) => {
      // Check if user already has a (possibly inactive) record for this exact unit
      const existingRecord = await tx.tenant.findUnique({
        where: { userId_unitId: { userId: user.id, unitId: invitation.unitId } },
      });

      let tenant;
      if (existingRecord) {
        if (existingRecord.isActive) {
          throw new BadRequestException('You are already assigned to this unit');
        }
        // Reactivate (tenant moved back in)
        tenant = await tx.tenant.update({
          where: { id: existingRecord.id },
          data: { isActive: true, moveInDate: new Date(), moveOutDate: null },
        });
      } else {
        tenant = await tx.tenant.create({
          data: {
            userId: user.id,
            unitId: invitation.unitId,
            isActive: true,
            moveInDate: new Date(),
          },
        });
      }

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

  async claimMatchingInvitations(userId: string) {
    const user = await this.prisma.user.findUnique({
      where: { id: userId },
      include: { role: true },
    });
    if (!user || user.role.name !== RoleName.TENANT) {
      throw new ForbiddenException('Only tenant accounts can claim unit invitations');
    }

    const email = user.email?.trim().toLowerCase();
    const phoneNumbers = this.phoneVariants(user.phoneNumber);
    const invitations = await this.prisma.invitation.findMany({
      where: {
        status: InvitationStatus.PENDING,
        expiresAt: { gte: new Date() },
        OR: [
          ...(email ? [{ tenantEmail: email }] : []),
          ...(phoneNumbers.length ? [{ phoneNumber: { in: phoneNumbers } }] : []),
        ],
      },
      orderBy: { createdAt: 'asc' },
    });

    let connectedCount = 0;
    for (const invitation of invitations) {
      try {
        await this.acceptInvitation(userId, { code: invitation.code });
        connectedCount += 1;
      } catch (error) {
        if (!(error instanceof BadRequestException)) throw error;
      }
    }

    return {
      connectedCount,
      message: connectedCount > 0
        ? `${connectedCount} rental ${connectedCount === 1 ? 'unit' : 'units'} connected automatically.`
        : 'No new rental invitations found.',
    };
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

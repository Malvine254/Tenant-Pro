import { RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { AcceptInvitationDto } from './dto/accept-invitation.dto';
import { CreateInvitationDto } from './dto/create-invitation.dto';
export declare class InvitationsService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    private generateUniqueCode;
    createInvitation(actorUserId: string, actorRole: RoleName, dto: CreateInvitationDto): Promise<{
        id: string;
        status: import(".prisma/client").$Enums.InvitationStatus;
        createdAt: Date;
        updatedAt: Date;
        phoneNumber: string;
        propertyId: string;
        unitId: string;
        metadata: import("@prisma/client/runtime/library").JsonValue | null;
        code: string;
        expiresAt: Date;
        acceptedAt: Date | null;
        sentVia: string | null;
        sentById: string;
    }>;
    acceptInvitation(userId: string, dto: AcceptInvitationDto): Promise<{
        tenant: {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            isActive: boolean;
            userId: string;
            unitId: string;
            moveInDate: Date;
            moveOutDate: Date | null;
        };
        invitation: {
            id: string;
            status: import(".prisma/client").$Enums.InvitationStatus;
            createdAt: Date;
            updatedAt: Date;
            phoneNumber: string;
            propertyId: string;
            unitId: string;
            metadata: import("@prisma/client/runtime/library").JsonValue | null;
            code: string;
            expiresAt: Date;
            acceptedAt: Date | null;
            sentVia: string | null;
            sentById: string;
        };
    }>;
    expirePendingInvitations(actorUserId: string, actorRole: RoleName): Promise<{
        message: string;
        updatedCount: number;
    }>;
}

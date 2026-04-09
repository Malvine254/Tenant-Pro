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
        code: string;
        phoneNumber: string;
        status: import(".prisma/client").$Enums.InvitationStatus;
        expiresAt: Date;
        acceptedAt: Date | null;
        sentVia: string | null;
        metadata: import("@prisma/client/runtime/library").JsonValue | null;
        createdAt: Date;
        updatedAt: Date;
        propertyId: string;
        unitId: string;
        sentById: string;
    }>;
    acceptInvitation(userId: string, dto: AcceptInvitationDto): Promise<{
        tenant: {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            unitId: string;
            userId: string;
            moveInDate: Date;
            moveOutDate: Date | null;
            isActive: boolean;
        };
        invitation: {
            id: string;
            code: string;
            phoneNumber: string;
            status: import(".prisma/client").$Enums.InvitationStatus;
            expiresAt: Date;
            acceptedAt: Date | null;
            sentVia: string | null;
            metadata: import("@prisma/client/runtime/library").JsonValue | null;
            createdAt: Date;
            updatedAt: Date;
            propertyId: string;
            unitId: string;
            sentById: string;
        };
    }>;
    expirePendingInvitations(actorUserId: string, actorRole: RoleName): Promise<{
        message: string;
        updatedCount: number;
    }>;
}

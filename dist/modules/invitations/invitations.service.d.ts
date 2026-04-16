import { RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { AcceptInvitationDto } from './dto/accept-invitation.dto';
import { CreateInvitationDto } from './dto/create-invitation.dto';
export declare class InvitationsService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    private generateUniqueCode;
    createInvitation(actorUserId: string, actorRole: RoleName, dto: CreateInvitationDto): Promise<{
        status: import(".prisma/client").$Enums.InvitationStatus;
        id: string;
        phoneNumber: string;
        createdAt: Date;
        updatedAt: Date;
        unitId: string;
        propertyId: string;
        code: string;
        sentVia: string | null;
        expiresAt: Date;
        acceptedAt: Date | null;
        metadata: import("@prisma/client/runtime/library").JsonValue | null;
        sentById: string;
    }>;
    acceptInvitation(userId: string, dto: AcceptInvitationDto): Promise<{
        tenant: {
            id: string;
            userId: string;
            createdAt: Date;
            updatedAt: Date;
            isActive: boolean;
            unitId: string;
            moveInDate: Date;
            moveOutDate: Date | null;
        };
        invitation: {
            status: import(".prisma/client").$Enums.InvitationStatus;
            id: string;
            phoneNumber: string;
            createdAt: Date;
            updatedAt: Date;
            unitId: string;
            propertyId: string;
            code: string;
            sentVia: string | null;
            expiresAt: Date;
            acceptedAt: Date | null;
            metadata: import("@prisma/client/runtime/library").JsonValue | null;
            sentById: string;
        };
    }>;
    expirePendingInvitations(actorUserId: string, actorRole: RoleName): Promise<{
        message: string;
        updatedCount: number;
    }>;
}

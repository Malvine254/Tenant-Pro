import { RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { AcceptInvitationDto } from './dto/accept-invitation.dto';
import { CreateInvitationDto } from './dto/create-invitation.dto';
export declare class InvitationsService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    private generateUniqueCode;
    createInvitation(landlordId: string, dto: CreateInvitationDto): Promise<{
        id: string;
        status: import(".prisma/client").$Enums.InvitationStatus;
        phoneNumber: string;
        createdAt: Date;
        updatedAt: Date;
        unitId: string;
        code: string;
        propertyId: string;
        sentById: string;
        expiresAt: Date;
        acceptedAt: Date | null;
        sentVia: string | null;
        metadata: import("@prisma/client/runtime/library").JsonValue | null;
    }>;
    acceptInvitation(userId: string, dto: AcceptInvitationDto): Promise<{
        tenant: {
            id: string;
            userId: string;
            createdAt: Date;
            updatedAt: Date;
            unitId: string;
            isActive: boolean;
            moveInDate: Date;
            moveOutDate: Date | null;
        };
        invitation: {
            id: string;
            status: import(".prisma/client").$Enums.InvitationStatus;
            phoneNumber: string;
            createdAt: Date;
            updatedAt: Date;
            unitId: string;
            code: string;
            propertyId: string;
            sentById: string;
            expiresAt: Date;
            acceptedAt: Date | null;
            sentVia: string | null;
            metadata: import("@prisma/client/runtime/library").JsonValue | null;
        };
    }>;
    expirePendingInvitations(actorUserId: string, actorRole: RoleName): Promise<{
        message: string;
        updatedCount: number;
    }>;
}

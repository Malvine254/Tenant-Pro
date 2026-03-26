import { RoleName } from '@prisma/client';
import { AcceptInvitationDto } from './dto/accept-invitation.dto';
import { CreateInvitationDto } from './dto/create-invitation.dto';
import { InvitationsService } from './invitations.service';
type AuthenticatedRequest = {
    user: {
        userId: string;
        role: RoleName;
        phoneNumber: string;
    };
};
export declare class InvitationsController {
    private readonly invitationsService;
    constructor(invitationsService: InvitationsService);
    create(req: AuthenticatedRequest, dto: CreateInvitationDto): Promise<{
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
    accept(req: AuthenticatedRequest, dto: AcceptInvitationDto): Promise<{
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
    expire(req: AuthenticatedRequest): Promise<{
        message: string;
        updatedCount: number;
    }>;
}
export {};

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
    accept(req: AuthenticatedRequest, dto: AcceptInvitationDto): Promise<{
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
    expire(req: AuthenticatedRequest): Promise<{
        message: string;
        updatedCount: number;
    }>;
}
export {};

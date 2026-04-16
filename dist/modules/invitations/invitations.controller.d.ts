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
    accept(req: AuthenticatedRequest, dto: AcceptInvitationDto): Promise<{
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
    expire(req: AuthenticatedRequest): Promise<{
        message: string;
        updatedCount: number;
    }>;
}
export {};

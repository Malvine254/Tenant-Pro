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
    accept(req: AuthenticatedRequest, dto: AcceptInvitationDto): Promise<{
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
    expire(req: AuthenticatedRequest): Promise<{
        message: string;
        updatedCount: number;
    }>;
}
export {};

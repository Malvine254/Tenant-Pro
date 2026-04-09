import { RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { CreatePropertyDto } from './dto/create-property.dto';
import { CreateUnitDto } from './dto/create-unit.dto';
import { UpdatePropertyDto } from './dto/update-property.dto';
import { UpdateUnitDto } from './dto/update-unit.dto';
export declare class PropertiesService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    createProperty(actorUserId: string, actorRole: RoleName, dto: CreatePropertyDto): Promise<{
        units: {
            id: string;
            status: import(".prisma/client").$Enums.UnitStatus;
            createdAt: Date;
            updatedAt: Date;
            propertyId: string;
            unitNumber: string;
            floor: string | null;
            rentAmount: import("@prisma/client/runtime/library").Decimal;
            imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
        }[];
    } & {
        id: string;
        createdAt: Date;
        updatedAt: Date;
        name: string;
        description: string | null;
        coverImageUrl: string | null;
        addressLine: string;
        city: string;
        state: string | null;
        country: string;
        landlordId: string;
    }>;
    addUnit(actorUserId: string, actorRole: RoleName, propertyId: string, dto: CreateUnitDto): Promise<{
        id: string;
        status: import(".prisma/client").$Enums.UnitStatus;
        createdAt: Date;
        updatedAt: Date;
        propertyId: string;
        unitNumber: string;
        floor: string | null;
        rentAmount: import("@prisma/client/runtime/library").Decimal;
        imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
    }>;
    updateProperty(actorUserId: string, actorRole: RoleName, propertyId: string, dto: UpdatePropertyDto): Promise<{
        landlord: {
            role: {
                id: string;
                createdAt: Date;
                updatedAt: Date;
                name: import(".prisma/client").$Enums.RoleName;
                description: string | null;
            };
        } & {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            phoneNumber: string;
            email: string | null;
            passwordHash: string | null;
            firstName: string | null;
            lastName: string | null;
            profileImageUrl: string | null;
            emergencyContactName: string | null;
            emergencyContactPhone: string | null;
            bio: string | null;
            isActive: boolean;
            roleId: string;
        };
        units: {
            id: string;
            status: import(".prisma/client").$Enums.UnitStatus;
            createdAt: Date;
            updatedAt: Date;
            propertyId: string;
            unitNumber: string;
            floor: string | null;
            rentAmount: import("@prisma/client/runtime/library").Decimal;
            imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
        }[];
    } & {
        id: string;
        createdAt: Date;
        updatedAt: Date;
        name: string;
        description: string | null;
        coverImageUrl: string | null;
        addressLine: string;
        city: string;
        state: string | null;
        country: string;
        landlordId: string;
    }>;
    updateUnit(actorUserId: string, actorRole: RoleName, unitId: string, dto: UpdateUnitDto): Promise<{
        id: string;
        status: import(".prisma/client").$Enums.UnitStatus;
        createdAt: Date;
        updatedAt: Date;
        propertyId: string;
        unitNumber: string;
        floor: string | null;
        rentAmount: import("@prisma/client/runtime/library").Decimal;
        imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
    }>;
    listProperties(actorUserId: string, actorRole: RoleName): Promise<({
        landlord: {
            role: {
                id: string;
                createdAt: Date;
                updatedAt: Date;
                name: import(".prisma/client").$Enums.RoleName;
                description: string | null;
            };
        } & {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            phoneNumber: string;
            email: string | null;
            passwordHash: string | null;
            firstName: string | null;
            lastName: string | null;
            profileImageUrl: string | null;
            emergencyContactName: string | null;
            emergencyContactPhone: string | null;
            bio: string | null;
            isActive: boolean;
            roleId: string;
        };
        units: {
            id: string;
            status: import(".prisma/client").$Enums.UnitStatus;
            createdAt: Date;
            updatedAt: Date;
            propertyId: string;
            unitNumber: string;
            floor: string | null;
            rentAmount: import("@prisma/client/runtime/library").Decimal;
            imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
        }[];
    } & {
        id: string;
        createdAt: Date;
        updatedAt: Date;
        name: string;
        description: string | null;
        coverImageUrl: string | null;
        addressLine: string;
        city: string;
        state: string | null;
        country: string;
        landlordId: string;
    })[]>;
}

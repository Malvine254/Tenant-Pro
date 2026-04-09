import { RoleName } from '@prisma/client';
import { CreatePropertyDto } from './dto/create-property.dto';
import { CreateUnitDto } from './dto/create-unit.dto';
import { PropertyIdParamDto } from './dto/property-id-param.dto';
import { UpdatePropertyDto } from './dto/update-property.dto';
import { UpdateUnitDto } from './dto/update-unit.dto';
import { PropertiesService } from './properties.service';
import { UnitIdParamDto } from '../units/dto/unit-id-param.dto';
type AuthenticatedRequest = {
    user: {
        userId: string;
        role: RoleName;
        phoneNumber: string;
    };
};
export declare class PropertiesController {
    private readonly propertiesService;
    constructor(propertiesService: PropertiesService);
    createProperty(req: AuthenticatedRequest, dto: CreatePropertyDto): Promise<{
        units: {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            propertyId: string;
            unitNumber: string;
            floor: string | null;
            rentAmount: import("@prisma/client/runtime/library").Decimal;
            status: import(".prisma/client").$Enums.UnitStatus;
            imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
        }[];
    } & {
        id: string;
        createdAt: Date;
        updatedAt: Date;
        name: string;
        description: string | null;
        landlordId: string;
        coverImageUrl: string | null;
        addressLine: string;
        city: string;
        state: string | null;
        country: string;
    }>;
    addUnit(req: AuthenticatedRequest, params: PropertyIdParamDto, dto: CreateUnitDto): Promise<{
        id: string;
        createdAt: Date;
        updatedAt: Date;
        propertyId: string;
        unitNumber: string;
        floor: string | null;
        rentAmount: import("@prisma/client/runtime/library").Decimal;
        status: import(".prisma/client").$Enums.UnitStatus;
        imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
    }>;
    listProperties(req: AuthenticatedRequest): Promise<({
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
            createdAt: Date;
            updatedAt: Date;
            roleId: string;
        };
        units: {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            propertyId: string;
            unitNumber: string;
            floor: string | null;
            rentAmount: import("@prisma/client/runtime/library").Decimal;
            status: import(".prisma/client").$Enums.UnitStatus;
            imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
        }[];
    } & {
        id: string;
        createdAt: Date;
        updatedAt: Date;
        name: string;
        description: string | null;
        landlordId: string;
        coverImageUrl: string | null;
        addressLine: string;
        city: string;
        state: string | null;
        country: string;
    })[]>;
    updateProperty(req: AuthenticatedRequest, params: PropertyIdParamDto, dto: UpdatePropertyDto): Promise<{
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
            createdAt: Date;
            updatedAt: Date;
            roleId: string;
        };
        units: {
            id: string;
            createdAt: Date;
            updatedAt: Date;
            propertyId: string;
            unitNumber: string;
            floor: string | null;
            rentAmount: import("@prisma/client/runtime/library").Decimal;
            status: import(".prisma/client").$Enums.UnitStatus;
            imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
        }[];
    } & {
        id: string;
        createdAt: Date;
        updatedAt: Date;
        name: string;
        description: string | null;
        landlordId: string;
        coverImageUrl: string | null;
        addressLine: string;
        city: string;
        state: string | null;
        country: string;
    }>;
    updateUnit(req: AuthenticatedRequest, params: UnitIdParamDto, dto: UpdateUnitDto): Promise<{
        id: string;
        createdAt: Date;
        updatedAt: Date;
        propertyId: string;
        unitNumber: string;
        floor: string | null;
        rentAmount: import("@prisma/client/runtime/library").Decimal;
        status: import(".prisma/client").$Enums.UnitStatus;
        imageUrls: import("@prisma/client/runtime/library").JsonValue | null;
    }>;
}
export {};

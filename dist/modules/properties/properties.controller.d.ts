import { RoleName } from '@prisma/client';
import { CreatePropertyDto } from './dto/create-property.dto';
import { CreateUnitDto } from './dto/create-unit.dto';
import { PropertyIdParamDto } from './dto/property-id-param.dto';
import { PropertiesService } from './properties.service';
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
            status: import(".prisma/client").$Enums.UnitStatus;
            createdAt: Date;
            updatedAt: Date;
            propertyId: string;
            unitNumber: string;
            floor: string | null;
            rentAmount: import("@prisma/client/runtime/library").Decimal;
        }[];
    } & {
        id: string;
        createdAt: Date;
        updatedAt: Date;
        name: string;
        description: string | null;
        landlordId: string;
        addressLine: string;
        city: string;
        state: string | null;
        country: string;
    }>;
    addUnit(req: AuthenticatedRequest, params: PropertyIdParamDto, dto: CreateUnitDto): Promise<{
        id: string;
        status: import(".prisma/client").$Enums.UnitStatus;
        createdAt: Date;
        updatedAt: Date;
        propertyId: string;
        unitNumber: string;
        floor: string | null;
        rentAmount: import("@prisma/client/runtime/library").Decimal;
    }>;
    listMyProperties(req: AuthenticatedRequest): Promise<({
        units: {
            id: string;
            status: import(".prisma/client").$Enums.UnitStatus;
            createdAt: Date;
            updatedAt: Date;
            propertyId: string;
            unitNumber: string;
            floor: string | null;
            rentAmount: import("@prisma/client/runtime/library").Decimal;
        }[];
    } & {
        id: string;
        createdAt: Date;
        updatedAt: Date;
        name: string;
        description: string | null;
        landlordId: string;
        addressLine: string;
        city: string;
        state: string | null;
        country: string;
    })[]>;
}
export {};

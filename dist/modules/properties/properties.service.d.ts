import { PrismaService } from '../../prisma/prisma.service';
import { CreatePropertyDto } from './dto/create-property.dto';
import { CreateUnitDto } from './dto/create-unit.dto';
export declare class PropertiesService {
    private readonly prisma;
    constructor(prisma: PrismaService);
    createProperty(landlordId: string, dto: CreatePropertyDto): Promise<{
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
    addUnit(landlordId: string, propertyId: string, dto: CreateUnitDto): Promise<{
        id: string;
        status: import(".prisma/client").$Enums.UnitStatus;
        createdAt: Date;
        updatedAt: Date;
        propertyId: string;
        unitNumber: string;
        floor: string | null;
        rentAmount: import("@prisma/client/runtime/library").Decimal;
    }>;
    listLandlordProperties(landlordId: string): Promise<({
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

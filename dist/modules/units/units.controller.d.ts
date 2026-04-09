import { RoleName } from '@prisma/client';
import { CreateUnitDto } from '../properties/dto/create-unit.dto';
import { PropertyIdParamDto } from '../properties/dto/property-id-param.dto';
import { PropertiesService } from '../properties/properties.service';
type AuthenticatedRequest = {
    user: {
        userId: string;
        role: RoleName;
        phoneNumber: string;
    };
};
export declare class UnitsController {
    private readonly propertiesService;
    constructor(propertiesService: PropertiesService);
    createUnitForProperty(req: AuthenticatedRequest, params: PropertyIdParamDto, dto: CreateUnitDto): Promise<{
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

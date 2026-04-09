import { UnitStatus } from '@prisma/client';
export declare class UpdateUnitDto {
    unitNumber?: string;
    floor?: string;
    rentAmount?: number;
    status?: UnitStatus;
    imageUrls?: string[];
}

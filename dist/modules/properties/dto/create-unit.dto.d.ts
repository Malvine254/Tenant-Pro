import { UnitStatus } from '@prisma/client';
export declare class CreateUnitDto {
    unitNumber: string;
    floor?: string;
    rentAmount: number;
    status?: UnitStatus;
    imageUrls?: string[];
}

import { MaintenancePriority } from '@prisma/client';
export declare class CreateRequestDto {
    title: string;
    description: string;
    priority?: MaintenancePriority;
}

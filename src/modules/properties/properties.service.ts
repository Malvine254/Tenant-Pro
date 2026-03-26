import { ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../../prisma/prisma.service';
import { CreatePropertyDto } from './dto/create-property.dto';
import { CreateUnitDto } from './dto/create-unit.dto';

@Injectable()
export class PropertiesService {
  constructor(private readonly prisma: PrismaService) {}

  async createProperty(landlordId: string, dto: CreatePropertyDto) {
    const property = await this.prisma.property.create({
      data: {
        landlordId,
        name: dto.name,
        description: dto.description,
        addressLine: dto.addressLine,
        city: dto.city,
        state: dto.state,
        country: dto.country,
      },
      include: {
        units: true,
      },
    });

    return property;
  }

  async addUnit(landlordId: string, propertyId: string, dto: CreateUnitDto) {
    const property = await this.prisma.property.findUnique({
      where: { id: propertyId },
    });

    if (!property) {
      throw new NotFoundException('Property not found');
    }

    if (property.landlordId !== landlordId) {
      throw new ForbiddenException('You can only add units to your own properties');
    }

    return this.prisma.unit.create({
      data: {
        propertyId,
        unitNumber: dto.unitNumber,
        floor: dto.floor,
        rentAmount: dto.rentAmount,
      },
    });
  }

  async listLandlordProperties(landlordId: string) {
    return this.prisma.property.findMany({
      where: { landlordId },
      include: {
        units: {
          orderBy: { unitNumber: 'asc' },
        },
      },
      orderBy: {
        createdAt: 'desc',
      },
    });
  }
}
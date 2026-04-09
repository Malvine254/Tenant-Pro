import { ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { RoleName } from '@prisma/client';
import { PrismaService } from '../../prisma/prisma.service';
import { CreatePropertyDto } from './dto/create-property.dto';
import { CreateUnitDto } from './dto/create-unit.dto';
import { UpdatePropertyDto } from './dto/update-property.dto';
import { UpdateUnitDto } from './dto/update-unit.dto';

@Injectable()
export class PropertiesService {
  constructor(private readonly prisma: PrismaService) {}

  async createProperty(actorUserId: string, actorRole: RoleName, dto: CreatePropertyDto) {
    let landlordId = actorUserId;

    if (actorRole === RoleName.ADMIN) {
      if (!dto.landlordId) {
        throw new ForbiddenException('Admin must provide landlordId when creating a property');
      }

      const landlord = await this.prisma.user.findUnique({
        where: { id: dto.landlordId },
        include: { role: true },
      });

      if (!landlord || landlord.role.name !== RoleName.LANDLORD) {
        throw new NotFoundException('Valid landlord user was not found for landlordId');
      }

      landlordId = dto.landlordId;
    }

    const property = await this.prisma.property.create({
      data: {
        landlordId,
        name: dto.name,
        description: dto.description,
        coverImageUrl: dto.coverImageUrl,
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

  async addUnit(actorUserId: string, actorRole: RoleName, propertyId: string, dto: CreateUnitDto) {
    const property = await this.prisma.property.findUnique({
      where: { id: propertyId },
    });

    if (!property) {
      throw new NotFoundException('Property not found');
    }

    if (actorRole !== RoleName.ADMIN && property.landlordId !== actorUserId) {
      throw new ForbiddenException('You can only add units to your own properties');
    }

    return this.prisma.unit.create({
      data: {
        propertyId,
        unitNumber: dto.unitNumber,
        floor: dto.floor,
        rentAmount: dto.rentAmount,
        status: dto.status,
        imageUrls: dto.imageUrls,
      },
    });
  }

  async updateProperty(
    actorUserId: string,
    actorRole: RoleName,
    propertyId: string,
    dto: UpdatePropertyDto,
  ) {
    const property = await this.prisma.property.findUnique({
      where: { id: propertyId },
      include: { landlord: { include: { role: true } } },
    });

    if (!property) {
      throw new NotFoundException('Property not found');
    }

    if (actorRole !== RoleName.ADMIN && property.landlordId !== actorUserId) {
      throw new ForbiddenException('You can only update your own properties');
    }

    let landlordId = property.landlordId;

    if (actorRole === RoleName.ADMIN && dto.landlordId) {
      const landlord = await this.prisma.user.findUnique({
        where: { id: dto.landlordId },
        include: { role: true },
      });

      if (!landlord || landlord.role.name !== RoleName.LANDLORD) {
        throw new NotFoundException('Valid landlord user was not found for landlordId');
      }

      landlordId = dto.landlordId;
    }

    return this.prisma.property.update({
      where: { id: propertyId },
      data: {
        landlordId,
        name: dto.name,
        description: dto.description,
        coverImageUrl: dto.coverImageUrl,
        addressLine: dto.addressLine,
        city: dto.city,
        state: dto.state,
        country: dto.country,
      },
      include: {
        landlord: { include: { role: true } },
        units: { orderBy: { unitNumber: 'asc' } },
      },
    });
  }

  async updateUnit(
    actorUserId: string,
    actorRole: RoleName,
    unitId: string,
    dto: UpdateUnitDto,
  ) {
    const unit = await this.prisma.unit.findUnique({
      where: { id: unitId },
      include: { property: true },
    });

    if (!unit) {
      throw new NotFoundException('Unit not found');
    }

    if (actorRole !== RoleName.ADMIN && unit.property.landlordId !== actorUserId) {
      throw new ForbiddenException('You can only update units in your own properties');
    }

    return this.prisma.unit.update({
      where: { id: unitId },
      data: {
        unitNumber: dto.unitNumber,
        floor: dto.floor,
        rentAmount: dto.rentAmount,
        status: dto.status,
        imageUrls: dto.imageUrls,
      },
    });
  }

  async listProperties(actorUserId: string, actorRole: RoleName) {
    return this.prisma.property.findMany({
      where: actorRole === RoleName.ADMIN ? {} : { landlordId: actorUserId },
      include: {
        landlord: {
          include: {
            role: true,
          },
        },
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
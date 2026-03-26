import { Module } from '@nestjs/common';
import { PropertiesModule } from '../properties/properties.module';
import { UnitsController } from './units.controller';

@Module({
  imports: [PropertiesModule],
  controllers: [UnitsController],
})
export class UnitsModule {}
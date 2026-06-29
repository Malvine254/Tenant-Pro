import { INestApplication, Injectable, Logger, OnModuleInit } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { PrismaClient } from '@prisma/client';

@Injectable()
export class PrismaService extends PrismaClient implements OnModuleInit {
  private readonly logger = new Logger(PrismaService.name);

  constructor(private readonly configService: ConfigService) {
    super();
  }

  async onModuleInit() {
    const useJsonDb =
      this.configService.get<string>('USE_JSON_DB', 'false').toLowerCase() === 'true';

    if (useJsonDb) {
      this.logger.warn('USE_JSON_DB is enabled. Skipping Prisma database connection.');
      return;
    }

    await this.$connect();
  }

  async enableShutdownHooks(app: INestApplication) {
    (this as PrismaClient).$on('beforeExit' as never, async () => {
      await app.close();
    });
  }
}
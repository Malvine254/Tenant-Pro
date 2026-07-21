import { Injectable, Logger, OnModuleInit } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import * as admin from 'firebase-admin';
import { existsSync, readFileSync } from 'fs';
import { join } from 'path';

@Injectable()
export class FirebaseService implements OnModuleInit {
  private readonly logger = new Logger(FirebaseService.name);
  private ready = false;

  constructor(private readonly configService: ConfigService) {}

  private loadServiceAccount() {
    const inlineJson = this.configService.get<string>('FIREBASE_SERVICE_ACCOUNT_JSON');
    if (inlineJson) {
      return JSON.parse(inlineJson);
    }

    const base64Json = this.configService.get<string>('FIREBASE_SERVICE_ACCOUNT_BASE64');
    if (base64Json) {
      return JSON.parse(Buffer.from(base64Json, 'base64').toString('utf8'));
    }

    const credentialsPath =
      this.configService.get<string>('FIREBASE_SERVICE_ACCOUNT_PATH') ??
      join(process.cwd(), 'firebase-service-account.json');

    if (!existsSync(credentialsPath)) {
      return null;
    }

    return JSON.parse(readFileSync(credentialsPath, 'utf8'));
  }

  onModuleInit() {
    if (admin.apps.length > 0) {
      this.ready = true;
      return;
    }
    try {
      const serviceAccount = this.loadServiceAccount();
      if (!serviceAccount) {
        this.logger.warn(
          'Firebase Admin SDK credentials were not found. Set FIREBASE_SERVICE_ACCOUNT_JSON, FIREBASE_SERVICE_ACCOUNT_BASE64, or FIREBASE_SERVICE_ACCOUNT_PATH.',
        );
        return;
      }

      admin.initializeApp({ credential: admin.credential.cert(serviceAccount) });
      this.ready = true;
      this.logger.log('Firebase Admin SDK initialized');
    } catch (e) {
      this.logger.error('Firebase Admin SDK failed to initialize — push notifications disabled', e);
    }
  }

  async sendPush(
    fcmToken: string,
    title: string,
    body: string,
    data?: Record<string, string>,
  ): Promise<void> {
    if (!this.ready) return;
    try {
      await admin.messaging().send({
        token: fcmToken,
        notification: { title, body },
        data,
        android: {
          priority: 'high',
          notification: { sound: 'default', channelId: 'tenantpro_default' },
        },
      });
    } catch (e) {
      this.logger.warn(`FCM push failed for token ${fcmToken.slice(0, 20)}…: ${e}`);
    }
  }
}

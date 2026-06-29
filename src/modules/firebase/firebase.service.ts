import { Injectable, Logger, OnModuleInit } from '@nestjs/common';
import * as admin from 'firebase-admin';
import { join } from 'path';

@Injectable()
export class FirebaseService implements OnModuleInit {
  private readonly logger = new Logger(FirebaseService.name);
  private ready = false;

  onModuleInit() {
    if (admin.apps.length > 0) {
      this.ready = true;
      return;
    }
    try {
      // eslint-disable-next-line @typescript-eslint/no-require-imports
      const serviceAccount = require(join(process.cwd(), 'firebase-service-account.json'));
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

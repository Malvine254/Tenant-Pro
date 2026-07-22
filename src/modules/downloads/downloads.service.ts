import { Injectable } from '@nestjs/common';
import { existsSync, statSync } from 'fs';
import { join } from 'path';

@Injectable()
export class DownloadsService {
  private readonly apkPath = join(
    process.cwd(),
    'tenant-app/app/build/outputs/apk/debug/app-debug.apk'
  );

  getApkInfo() {
    if (!existsSync(this.apkPath)) {
      return null;
    }

    const stats = statSync(this.apkPath);
    return {
      filename: 'app-debug.apk',
      path: this.apkPath,
      size: stats.size,
      sizeInMB: (stats.size / (1024 * 1024)).toFixed(2),
      lastModified: stats.mtime,
    };
  }
}

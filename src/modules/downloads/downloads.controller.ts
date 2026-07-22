import { Controller, Get, Res, HttpException, HttpStatus, UseGuards } from '@nestjs/common';
import { Response } from 'express';
import { createReadStream, existsSync } from 'fs';
import { DownloadsService } from './downloads.service';

@Controller('downloads')
export class DownloadsController {
  constructor(private readonly downloadsService: DownloadsService) {}

  @Get('apk-info')
  getApkInfo() {
    const info = this.downloadsService.getApkInfo();
    if (!info) {
      throw new HttpException(
        'APK not found. Please build the Android app first.',
        HttpStatus.NOT_FOUND
      );
    }
    return {
      filename: info.filename,
      size: info.size,
      sizeInMB: info.sizeInMB,
      lastModified: info.lastModified,
    };
  }

  @Get('apk')
  downloadApk(@Res() res: Response) {
    const info = this.downloadsService.getApkInfo();
    if (!info) {
      throw new HttpException(
        'APK not found. Please build the Android app first.',
        HttpStatus.NOT_FOUND
      );
    }

    if (!existsSync(info.path)) {
      throw new HttpException(
        'APK file not accessible.',
        HttpStatus.NOT_FOUND
      );
    }

    res.setHeader(
      'Content-Disposition',
      `attachment; filename="${info.filename}"`
    );
    res.setHeader('Content-Type', 'application/vnd.android.package-archive');
    res.setHeader('Content-Length', info.size);

    const stream = createReadStream(info.path);
    stream.pipe(res);
  }
}

import { BadRequestException, Body, Controller, Get, Param, Post, Req, UploadedFile, UseGuards, UseInterceptors } from '@nestjs/common';
import { FileInterceptor } from '@nestjs/platform-express';
import { RoleName } from '@prisma/client';
import { existsSync, mkdirSync } from 'fs';
import { extname, join } from 'path';

const { diskStorage } = require('multer');
import { Roles } from '../../common/decorators/roles.decorator';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { SendSupportMessageDto } from './dto/send-support-message.dto';
import { SupportService } from './support.service';

type AuthenticatedRequest = {
  user: {
    userId: string;
    role: RoleName;
    phoneNumber: string;
  };
};

const uploadDir = join(process.cwd(), 'uploads', 'support');
if (!existsSync(uploadDir)) {
  mkdirSync(uploadDir, { recursive: true });
}

@Controller('support')
@UseGuards(JwtAuthGuard, RolesGuard)
export class SupportController {
  constructor(private readonly supportService: SupportService) {}

  @Post('upload')
  @Roles(RoleName.TENANT, RoleName.LANDLORD, RoleName.ADMIN, RoleName.CARETAKER)
  @UseInterceptors(
    FileInterceptor('file', {
      storage: diskStorage({
        destination: (_req: unknown, _file: unknown, cb: (error: Error | null, destination: string) => void) => cb(null, uploadDir),
        filename: (
          _req: unknown,
          file: { originalname: string },
          cb: (error: Error | null, filename: string) => void,
        ) => {
          const safeBase = file.originalname.replace(/[^a-zA-Z0-9._-]/g, '_').replace(/\.[^.]+$/, '');
          const unique = `${Date.now()}-${Math.round(Math.random() * 1e9)}`;
          cb(null, `${safeBase || 'attachment'}-${unique}${extname(file.originalname)}`);
        },
      }),
      limits: { fileSize: 10 * 1024 * 1024 },
    }),
  )
  uploadAttachment(
    @UploadedFile()
    file?: { originalname: string; filename: string; size: number; mimetype: string },
  ) {
    if (!file) {
      throw new BadRequestException('No file uploaded');
    }

    return {
      fileName: file.originalname,
      attachmentName: file.originalname,
      attachmentUri: `/uploads/support/${file.filename}`,
      size: file.size,
      mimeType: file.mimetype,
    };
  }

  @Get('messages')
  @Roles(RoleName.TENANT, RoleName.LANDLORD, RoleName.ADMIN, RoleName.CARETAKER)
  listMessages(@Req() req: AuthenticatedRequest) {
    return this.supportService.listMessages(req.user.userId);
  }

  @Post('messages')
  @Roles(RoleName.TENANT, RoleName.LANDLORD, RoleName.ADMIN, RoleName.CARETAKER)
  sendMessage(@Req() req: AuthenticatedRequest, @Body() dto: SendSupportMessageDto) {
    return this.supportService.sendMessage(req.user.userId, dto);
  }

  @Get('conversations')
  @Roles(RoleName.ADMIN, RoleName.LANDLORD, RoleName.CARETAKER)
  listConversations() {
    return this.supportService.listConversationsForOps();
  }

  @Post('conversations')
  @Roles(RoleName.ADMIN, RoleName.LANDLORD, RoleName.CARETAKER)
  startConversation(@Req() req: AuthenticatedRequest, @Body() dto: SendSupportMessageDto) {
    return this.supportService.startConversationForOps(req.user.userId, dto);
  }

  @Get('conversations/:conversationId/messages')
  @Roles(RoleName.ADMIN, RoleName.LANDLORD, RoleName.CARETAKER)
  getConversationMessages(@Param('conversationId') conversationId: string) {
    return this.supportService.getConversationMessages(conversationId);
  }

  @Post('conversations/:conversationId/messages')
  @Roles(RoleName.ADMIN, RoleName.LANDLORD, RoleName.CARETAKER)
  replyToConversation(
    @Req() req: AuthenticatedRequest,
    @Param('conversationId') conversationId: string,
    @Body() dto: SendSupportMessageDto,
  ) {
    return this.supportService.replyToConversation(req.user.userId, conversationId, dto);
  }
}

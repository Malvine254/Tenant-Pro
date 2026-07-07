<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantProUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $preheader,
        public string $title,
        public array $introLines = [],
        public array $details = [],
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
        public ?string $footerText = null,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine)
            ->view('emails.tenant-pro-update')
            ->with([
                'subjectLine' => $this->subjectLine,
                'preheader' => $this->preheader,
                'title' => $this->title,
                'introLines' => $this->introLines,
                'details' => $this->details,
                'actionLabel' => $this->actionLabel,
                'actionUrl' => $this->actionUrl,
                'footerText' => $this->footerText,
            ]);
    }
}

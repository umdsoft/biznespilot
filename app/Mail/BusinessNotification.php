<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BusinessNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Business $business,
        public ?User $user,
        public string $type,
        public string $notificationTitle,
        public string $notificationMessage,
        public ?string $actionUrl = null,
        public ?string $actionText = null,
        public array $extraData = []
    ) {}

    public function envelope(): Envelope
    {
        $subjectPrefix = match ($this->type) {
            'alert' => '⚠️',
            'kpi' => '📊',
            'task' => '✅',
            'lead' => '👤',
            'report' => '📈',
            'celebration' => '🎉',
            default => '📢',
        };

        return new Envelope(
            subject: "{$subjectPrefix} {$this->notificationTitle} - {$this->business->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.business-notification',
            with: [
                'business' => $this->business,
                'user' => $this->user,
                'type' => $this->type,
                'title' => $this->notificationTitle,
                'message' => $this->notificationMessage,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
                'extraData' => $this->extraData,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

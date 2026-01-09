<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public Business $business;
    public User $inviter;
    public BusinessUser $member;
    public string $token;
    public string $acceptUrl;

    public function __construct(Business $business, User $inviter, BusinessUser $member, string $token)
    {
        $this->business = $business;
        $this->inviter = $inviter;
        $this->member = $member;
        $this->token = $token;
        $this->acceptUrl = route('invite.show', $token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->business->name} jamoasiga taklif",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.team-invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

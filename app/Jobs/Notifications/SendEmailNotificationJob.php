<?php

namespace App\Jobs\Notifications;

use App\Mail\BusinessNotification;
use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 60;

    public function __construct(
        public NotificationDelivery $delivery
    ) {}

    public function handle(): void
    {
        $delivery = $this->delivery;

        if (!$delivery->isPending()) {
            return;
        }

        $email = $delivery->metadata['email'] ?? null;
        if (!$email) {
            // Try to get from user
            if ($delivery->user_id) {
                $user = User::find($delivery->user_id);
                $email = $user?->email;
            }
        }

        if (!$email) {
            $delivery->markAsFailed('Email manzil topilmadi');
            return;
        }

        $business = $delivery->business;
        $user = $delivery->user;

        try {
            $mailable = new BusinessNotification(
                business: $business,
                user: $user,
                type: $delivery->type,
                notificationTitle: $delivery->title,
                notificationMessage: $delivery->message,
                actionUrl: $delivery->metadata['action_url'] ?? null,
                actionText: $delivery->metadata['action_text'] ?? null,
                extraData: $delivery->metadata['extra_data'] ?? []
            );

            Mail::to($email)->send($mailable);

            $delivery->markAsSent();

            Log::info('Email notification sent', [
                'delivery_id' => $delivery->id,
                'email' => $email,
            ]);
        } catch (\Exception $e) {
            Log::error('Email notification failed', [
                'delivery_id' => $delivery->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $delivery->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }
}

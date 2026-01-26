<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ContentCalendarResource - Kontent Kalendarni JSON formatida qaytarish
 *
 * Bu resource content_calendar ma'lumotlarini frontend uchun formatlaydi.
 * Instagram statistikalarini ham qo'shadi (agar bog'langan bo'lsa).
 */
class ContentCalendarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Instagram link dan real statistikalarni olish
        $instagramLink = $this->whenLoaded('instagramContentLink');

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'content' => $this->content_text ?? $this->description,
            'description' => $this->description,

            // Platform/Channel
            'platform' => $this->channel,
            'channel' => $this->channel,
            'channel_label' => $this->getChannelLabel(),
            'channel_account' => $this->channel_account,

            // Content categorization
            'content_type' => $this->content_type,
            'content_type_label' => $this->getContentTypeLabel(),
            'format' => $this->format,
            'goal' => $this->goal,
            'goal_label' => $this->when($this->goal, fn () => $this->getGoalLabel()),

            // Media
            'media_urls' => $this->media_urls,
            'hashtags' => $this->hashtags,
            'tags' => $this->tags,

            // Status & Scheduling
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'scheduled_time' => $this->scheduled_time,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
            'timezone' => $this->timezone,

            // External links
            'external_post_id' => $this->external_post_id,
            'post_url' => $this->post_url,

            // ========================================
            // INSTAGRAM STATISTIKALAR
            // ========================================
            'instagram_link' => $this->when($instagramLink, function () use ($instagramLink) {
                return [
                    'id' => $instagramLink->id,
                    'instagram_media_id' => $instagramLink->instagram_media_id,
                    'media_type' => $instagramLink->media_type,
                    'permalink' => $instagramLink->permalink,
                    'thumbnail_url' => $instagramLink->thumbnail_url,
                    'posted_at' => $instagramLink->posted_at?->toISOString(),

                    // Real-time stats
                    'views' => $instagramLink->views,
                    'likes' => $instagramLink->likes,
                    'comments' => $instagramLink->comments,
                    'shares' => $instagramLink->shares,
                    'saves' => $instagramLink->saves,
                    'reach' => $instagramLink->reach,
                    'impressions' => $instagramLink->impressions,

                    // Calculated metrics
                    'engagement_rate' => round((float) $instagramLink->engagement_rate, 2),
                    'save_rate' => round((float) $instagramLink->save_rate, 2),
                    'share_rate' => round((float) $instagramLink->share_rate, 2),

                    // Performance
                    'performance_score' => $instagramLink->performance_score,
                    'is_top_performer' => $instagramLink->is_top_performer,

                    // Sync info
                    'sync_status' => $instagramLink->sync_status,
                    'last_synced_at' => $instagramLink->last_synced_at?->toISOString(),
                    'last_synced_human' => $instagramLink->last_synced_at?->diffForHumans(),
                ];
            }),

            // Fallback statistikalar (Instagram link yo'q bo'lsa)
            'views' => $this->when(
                ! $instagramLink,
                $this->views,
                $instagramLink?->views ?? $this->views
            ),
            'likes' => $this->when(
                ! $instagramLink,
                $this->likes,
                $instagramLink?->likes ?? $this->likes
            ),
            'comments' => $this->when(
                ! $instagramLink,
                $this->comments,
                $instagramLink?->comments ?? $this->comments
            ),
            'shares' => $this->when(
                ! $instagramLink,
                $this->shares,
                $instagramLink?->shares ?? $this->shares
            ),
            'saves' => $this->when(
                ! $instagramLink,
                $this->saves,
                $instagramLink?->saves ?? $this->saves
            ),
            'reach' => $this->when(
                ! $instagramLink,
                $this->reach,
                $instagramLink?->reach ?? $this->reach
            ),
            'engagement_rate' => $this->when(
                ! $instagramLink,
                $this->engagement_rate,
                $instagramLink?->engagement_rate ?? $this->engagement_rate
            ),

            // Computed stats flags (for UI)
            'has_instagram_link' => $instagramLink !== null,
            'is_top_performer' => $instagramLink?->is_top_performer ?? false,
            'performance_score' => $instagramLink?->performance_score ?? 0,

            // Campaign
            'campaign_name' => $this->campaign_name,
            'campaign_id' => $this->campaign_id,

            // AI
            'is_ai_generated' => $this->is_ai_generated,
            'ai_suggestions' => $this->ai_suggestions,
            'ai_caption_suggestion' => $this->ai_caption_suggestion,

            // Meta
            'theme' => $this->theme,
            'notes' => $this->notes,
            'priority' => $this->priority,
            'sort_order' => $this->sort_order,

            // Approval
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

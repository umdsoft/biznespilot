<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramAutomationTrigger extends Model
{
    protected $fillable = [
        'automation_id',
        'trigger_type',
        'keywords',
        'media_id',
        'case_sensitive',
        'exact_match',
    ];

    protected $casts = [
        'keywords' => 'array',
        'case_sensitive' => 'boolean',
        'exact_match' => 'boolean',
    ];

    // Trigger type constants
    const TYPE_KEYWORD_DM = 'keyword_dm';
    const TYPE_KEYWORD_COMMENT = 'keyword_comment';
    const TYPE_STORY_MENTION = 'story_mention';
    const TYPE_STORY_REPLY = 'story_reply';
    const TYPE_NEW_FOLLOWER = 'new_follower';
    const TYPE_POST_LIKE = 'post_like';
    const TYPE_POST_SAVE = 'post_save';
    const TYPE_REEL_COMMENT = 'reel_comment';
    const TYPE_MEDIA_SHARE = 'media_share';

    public function automation(): BelongsTo
    {
        return $this->belongsTo(InstagramAutomation::class, 'automation_id');
    }

    public function matches(string $text): bool
    {
        if (empty($this->keywords)) {
            return false;
        }

        $searchText = $this->case_sensitive ? $text : mb_strtolower($text);

        foreach ($this->keywords as $keyword) {
            $searchKeyword = $this->case_sensitive ? $keyword : mb_strtolower($keyword);

            if ($this->exact_match) {
                if ($searchText === $searchKeyword) {
                    return true;
                }
            } else {
                if (str_contains($searchText, $searchKeyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getMatchingKeyword(string $text): ?string
    {
        if (empty($this->keywords)) {
            return null;
        }

        $searchText = $this->case_sensitive ? $text : mb_strtolower($text);

        foreach ($this->keywords as $keyword) {
            $searchKeyword = $this->case_sensitive ? $keyword : mb_strtolower($keyword);

            if ($this->exact_match) {
                if ($searchText === $searchKeyword) {
                    return $keyword;
                }
            } else {
                if (str_contains($searchText, $searchKeyword)) {
                    return $keyword;
                }
            }
        }

        return null;
    }

    public static function getTriggerTypeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_KEYWORD_DM => 'DM kalit so\'z',
            self::TYPE_KEYWORD_COMMENT => 'Comment kalit so\'z',
            self::TYPE_STORY_MENTION => 'Story mention',
            self::TYPE_STORY_REPLY => 'Story reply',
            self::TYPE_NEW_FOLLOWER => 'Yangi follower',
            self::TYPE_POST_LIKE => 'Post like',
            self::TYPE_POST_SAVE => 'Post save',
            self::TYPE_REEL_COMMENT => 'Reel comment',
            self::TYPE_MEDIA_SHARE => 'Media share',
            default => $type,
        };
    }
}

<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FeedbackAttachment extends Model
{
    use HasUuid;

    // Max file size in bytes (5MB)
    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    // Allowed file types
    public const ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx'];

    protected $fillable = [
        'feedback_report_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    // ==================== Relationships ====================

    public function feedbackReport(): BelongsTo
    {
        return $this->belongsTo(FeedbackReport::class);
    }

    // ==================== Accessors ====================

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->file_type, 'image/');
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    // ==================== Methods ====================

    public function deleteFile(): bool
    {
        if (Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->delete($this->file_path);
        }

        return true;
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            $attachment->deleteFile();
        });
    }
}

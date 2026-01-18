<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TodoTemplateItem extends Model
{
    use HasUuid;

    // Default assignee roles
    public const ROLE_OWNER = 'owner';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_OPERATOR = 'operator';

    public const ROLES = [
        self::ROLE_OWNER => 'Egasi',
        self::ROLE_MANAGER => 'Menejer',
        self::ROLE_OPERATOR => 'Operator',
    ];

    protected $fillable = [
        'template_id',
        'parent_id',
        'title',
        'description',
        'order',
        'default_assignee_role',
        'due_days_offset',
    ];

    protected $casts = [
        'order' => 'integer',
        'due_days_offset' => 'integer',
    ];

    // ==================== Relationships ====================

    public function template(): BelongsTo
    {
        return $this->belongsTo(TodoTemplate::class, 'template_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    // ==================== Accessors ====================

    public function getRoleLabelAttribute(): ?string
    {
        if (! $this->default_assignee_role) {
            return null;
        }

        return self::ROLES[$this->default_assignee_role] ?? $this->default_assignee_role;
    }

    public function getDueDaysLabelAttribute(): ?string
    {
        if ($this->due_days_offset === null) {
            return null;
        }

        if ($this->due_days_offset === 0) {
            return 'Bugun';
        }

        if ($this->due_days_offset === 1) {
            return 'Ertaga';
        }

        return "{$this->due_days_offset} kundan keyin";
    }

    public function getHasChildrenAttribute(): bool
    {
        return $this->children()->exists();
    }

    public function getDepthAttribute(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    // ==================== Methods ====================

    /**
     * Reorder item
     */
    public function reorder(int $newOrder): void
    {
        $oldOrder = $this->order;

        if ($newOrder === $oldOrder) {
            return;
        }

        $query = self::where('template_id', $this->template_id)
            ->where('parent_id', $this->parent_id)
            ->where('id', '!=', $this->id);

        if ($newOrder > $oldOrder) {
            // Moving down
            $query->whereBetween('order', [$oldOrder + 1, $newOrder])
                ->decrement('order');
        } else {
            // Moving up
            $query->whereBetween('order', [$newOrder, $oldOrder - 1])
                ->increment('order');
        }

        $this->update(['order' => $newOrder]);
    }

    /**
     * Add child item
     */
    public function addChild(array $data): self
    {
        $maxOrder = $this->children()->max('order') ?? -1;

        return $this->children()->create([
            'template_id' => $this->template_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'order' => $maxOrder + 1,
            'default_assignee_role' => $data['default_assignee_role'] ?? null,
            'due_days_offset' => $data['due_days_offset'] ?? null,
        ]);
    }

    /**
     * Move to different parent
     */
    public function moveTo(?string $newParentId): void
    {
        if ($this->parent_id === $newParentId) {
            return;
        }

        // Get max order in new location
        $maxOrder = self::where('template_id', $this->template_id)
            ->where('parent_id', $newParentId)
            ->max('order') ?? -1;

        $this->update([
            'parent_id' => $newParentId,
            'order' => $maxOrder + 1,
        ]);
    }
}

<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class TodoTemplate extends Model
{
    use BelongsToBusiness, HasUuid;

    // Categories
    public const CATEGORY_ONBOARDING = 'onboarding';

    public const CATEGORY_SALES = 'sales';

    public const CATEGORY_OPERATIONS = 'operations';

    public const CATEGORY_MARKETING = 'marketing';

    public const CATEGORY_CUSTOM = 'custom';

    public const CATEGORIES = [
        self::CATEGORY_ONBOARDING => 'Onboarding',
        self::CATEGORY_SALES => 'Sotuv',
        self::CATEGORY_OPERATIONS => 'Operatsiya',
        self::CATEGORY_MARKETING => 'Marketing',
        self::CATEGORY_CUSTOM => 'Boshqa',
    ];

    public const CATEGORY_ICONS = [
        self::CATEGORY_ONBOARDING => '👤',
        self::CATEGORY_SALES => '💼',
        self::CATEGORY_OPERATIONS => '⚙️',
        self::CATEGORY_MARKETING => '📢',
        self::CATEGORY_CUSTOM => '📋',
    ];

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'category',
        'icon',
        'color',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function items(): HasMany
    {
        return $this->hasMany(TodoTemplateItem::class, 'template_id')->orderBy('order');
    }

    public function rootItems(): HasMany
    {
        return $this->hasMany(TodoTemplateItem::class, 'template_id')
            ->whereNull('parent_id')
            ->orderBy('order');
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class, 'template_id');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ==================== Accessors ====================

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getCategoryIconAttribute(): string
    {
        return $this->icon ?? self::CATEGORY_ICONS[$this->category] ?? '📋';
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    // ==================== Methods ====================

    /**
     * Create todos from this template
     */
    public function createTodosFromTemplate(
        ?string $assigneeId = null,
        ?string $type = null,
        ?\DateTimeInterface $baseDate = null
    ): Collection {
        // Convert to Carbon for copy() method support
        $baseDate = $baseDate ? Carbon::parse($baseDate) : now();
        $createdTodos = collect();
        $itemToTodoMap = [];

        // Get root items with their children
        $rootItems = $this->rootItems()->with('children')->get();

        foreach ($rootItems as $item) {
            $todo = $this->createTodoFromItem($item, null, $assigneeId, $type, $baseDate, $itemToTodoMap);
            $createdTodos->push($todo);
        }

        // Increment usage count
        $this->increment('usage_count');

        return $createdTodos;
    }

    /**
     * Create a single todo from template item
     */
    protected function createTodoFromItem(
        TodoTemplateItem $item,
        ?string $parentId,
        ?string $assigneeId,
        ?string $type,
        Carbon $baseDate,
        array &$itemToTodoMap
    ): Todo {
        // Determine assignee
        $finalAssigneeId = $assigneeId;
        if (! $finalAssigneeId && $item->default_assignee_role) {
            $finalAssigneeId = $this->resolveAssigneeByRole($item->default_assignee_role);
        }

        // Calculate due date
        $dueDate = null;
        if ($item->due_days_offset !== null) {
            $dueDate = $baseDate->copy()->addDays($item->due_days_offset);
        }

        $todo = Todo::create([
            'business_id' => $this->business_id,
            'created_by' => Auth::id(),
            'assigned_to' => $finalAssigneeId,
            'parent_id' => $parentId,
            'title' => $item->title,
            'description' => $item->description,
            'type' => $type ?? Todo::TYPE_PROCESS,
            'priority' => Todo::PRIORITY_MEDIUM,
            'status' => Todo::STATUS_PENDING,
            'due_date' => $dueDate,
            'order' => $item->order,
            'template_id' => $this->id,
        ]);

        $itemToTodoMap[$item->id] = $todo->id;

        // Create child todos
        foreach ($item->children as $childItem) {
            $this->createTodoFromItem($childItem, $todo->id, $assigneeId, $type, $baseDate, $itemToTodoMap);
        }

        return $todo;
    }

    /**
     * Resolve assignee by role
     */
    protected function resolveAssigneeByRole(string $role): ?string
    {
        // Get business members by role
        $business = $this->business;

        if (! $business) {
            return null;
        }

        $member = $business->teamMembers()
            ->wherePivot('role', $role)
            ->first();

        return $member?->id;
    }

    /**
     * Duplicate this template
     */
    public function duplicate(?string $newName = null): self
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = $newName ?? $this->name.' (nusxa)';
        $newTemplate->usage_count = 0;
        $newTemplate->save();

        // Duplicate items with hierarchy
        $this->duplicateItems($this->rootItems, $newTemplate->id, null);

        return $newTemplate->fresh(['items']);
    }

    /**
     * Duplicate template items recursively
     */
    protected function duplicateItems($items, string $templateId, ?string $parentId): void
    {
        foreach ($items as $item) {
            $newItem = $item->replicate();
            $newItem->template_id = $templateId;
            $newItem->parent_id = $parentId;
            $newItem->save();

            // Duplicate children
            if ($item->children->isNotEmpty()) {
                $this->duplicateItems($item->children, $templateId, $newItem->id);
            }
        }
    }

    /**
     * Add item to template
     */
    public function addItem(array $data, ?string $parentId = null): TodoTemplateItem
    {
        $maxOrder = $this->items()
            ->where('parent_id', $parentId)
            ->max('order') ?? -1;

        return $this->items()->create([
            'parent_id' => $parentId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'order' => $maxOrder + 1,
            'default_assignee_role' => $data['default_assignee_role'] ?? null,
            'due_days_offset' => $data['due_days_offset'] ?? null,
        ]);
    }
}

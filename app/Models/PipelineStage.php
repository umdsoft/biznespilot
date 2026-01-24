<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PipelineStage extends Model
{
    use BelongsToBusiness, HasFactory;
    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'color',
        'order',
        'is_system',
        'is_won',
        'is_lost',
        'is_active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Available colors for stages
     */
    public const COLORS = [
        'blue' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-600', 'light' => 'bg-blue-100'],
        'indigo' => ['bg' => 'bg-indigo-500', 'text' => 'text-indigo-600', 'light' => 'bg-indigo-100'],
        'purple' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-600', 'light' => 'bg-purple-100'],
        'pink' => ['bg' => 'bg-pink-500', 'text' => 'text-pink-600', 'light' => 'bg-pink-100'],
        'red' => ['bg' => 'bg-red-500', 'text' => 'text-red-600', 'light' => 'bg-red-100'],
        'orange' => ['bg' => 'bg-orange-500', 'text' => 'text-orange-600', 'light' => 'bg-orange-100'],
        'yellow' => ['bg' => 'bg-yellow-500', 'text' => 'text-yellow-600', 'light' => 'bg-yellow-100'],
        'green' => ['bg' => 'bg-green-500', 'text' => 'text-green-600', 'light' => 'bg-green-100'],
        'teal' => ['bg' => 'bg-teal-500', 'text' => 'text-teal-600', 'light' => 'bg-teal-100'],
        'cyan' => ['bg' => 'bg-cyan-500', 'text' => 'text-cyan-600', 'light' => 'bg-cyan-100'],
        'gray' => ['bg' => 'bg-gray-500', 'text' => 'text-gray-600', 'light' => 'bg-gray-100'],
    ];

    /**
     * Get leads in this stage.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'status', 'slug')
            ->where('business_id', $this->business_id);
    }

    /**
     * Scope to get only active stages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order stages by their order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope to get stages for a specific business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Generate unique slug for this business.
     */
    public static function generateSlug(string $name, string $businessId, ?int $excludeId = null): string
    {
        $slug = Str::slug($name, '_');
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('business_id', $businessId)
            ->where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $originalSlug . '_' . $counter++;
        }

        return $slug;
    }

    /**
     * Create default stages for a new business.
     * Only creates essential system stages - users can add custom stages later.
     */
    public static function createDefaultStages(string $businessId): void
    {
        $defaultStages = [
            ['name' => 'Yangi', 'slug' => 'new', 'color' => 'blue', 'order' => 1, 'is_system' => true],
            ['name' => 'Sotuv', 'slug' => 'won', 'color' => 'green', 'order' => 100, 'is_system' => true, 'is_won' => true],
            ['name' => 'Sifatsiz lid', 'slug' => 'lost', 'color' => 'red', 'order' => 101, 'is_system' => true, 'is_lost' => true],
        ];

        foreach ($defaultStages as $stage) {
            static::create([
                'business_id' => $businessId,
                'name' => $stage['name'],
                'slug' => $stage['slug'],
                'color' => $stage['color'],
                'order' => $stage['order'],
                'is_system' => $stage['is_system'] ?? false,
                'is_won' => $stage['is_won'] ?? false,
                'is_lost' => $stage['is_lost'] ?? false,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Check if this stage can be deleted.
     */
    public function canBeDeleted(): bool
    {
        // System stages cannot be deleted
        if ($this->is_system) {
            return false;
        }

        return true;
    }

    /**
     * Check if this stage can be edited.
     */
    public function canBeEdited(): bool
    {
        // System stages can only have name edited, not slug or is_won/is_lost
        return !$this->is_system;
    }

    /**
     * Get the next available order number.
     */
    public static function getNextOrder(string $businessId): int
    {
        // Get max order that is less than 100 (won/lost are 100+)
        $maxOrder = static::where('business_id', $businessId)
            ->where('order', '<', 100)
            ->max('order');

        return ($maxOrder ?? 0) + 1;
    }
}

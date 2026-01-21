<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Function Knowledge Registry - Track who knows what
 * Critical for identifying "star employee" risks
 * If only one person knows a critical function - it's a risk!
 */
class FunctionKnowledgeRegistry extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $table = 'function_knowledge_registry';

    protected $fillable = [
        'business_id',
        'function_name',
        'description',
        'criticality',
        'knowledgeable_users',
        'knowledge_holders_count',
        'is_at_risk',
        'is_documented',
        'documentation_link',
    ];

    protected $casts = [
        'knowledgeable_users' => 'array',
        'is_at_risk' => 'boolean',
        'is_documented' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function ($registry) {
            // Update knowledge holders count
            $registry->knowledge_holders_count = count($registry->knowledgeable_users ?? []);

            // Check if at risk (only 1 person knows critical function)
            $registry->is_at_risk = $registry->knowledge_holders_count <= 1
                && in_array($registry->criticality, ['high', 'critical']);
        });
    }

    // Get criticality label
    public function getCriticalityLabelAttribute(): string
    {
        return match($this->criticality) {
            'low' => 'Past',
            'medium' => 'O\'rta',
            'high' => 'Yuqori',
            'critical' => 'Kritik',
            default => $this->criticality,
        };
    }

    // Get criticality color
    public function getCriticalityColorAttribute(): string
    {
        return match($this->criticality) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }

    // Get risk status label
    public function getRiskStatusLabelAttribute(): string
    {
        if (!$this->is_at_risk) return 'Xavfsiz';

        return $this->knowledge_holders_count === 0
            ? 'KRITIK: Hech kim bilmaydi!'
            : 'XAVF: Faqat 1 kishi biladi!';
    }

    // Add user to knowledgeable list
    public function addKnowledgeableUser(string $userId): void
    {
        $users = $this->knowledgeable_users ?? [];

        if (!in_array($userId, $users)) {
            $users[] = $userId;
            $this->knowledgeable_users = $users;
            $this->save();
        }
    }

    // Remove user from knowledgeable list
    public function removeKnowledgeableUser(string $userId): void
    {
        $users = $this->knowledgeable_users ?? [];
        $users = array_filter($users, fn($id) => $id !== $userId);
        $this->knowledgeable_users = array_values($users);
        $this->save();
    }

    // Scopes
    public function scopeAtRisk($query)
    {
        return $query->where('is_at_risk', true);
    }

    public function scopeCritical($query)
    {
        return $query->where('criticality', 'critical');
    }

    public function scopeNotDocumented($query)
    {
        return $query->where('is_documented', false);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->where(function($q) {
            $q->where('is_at_risk', true)
              ->orWhere(function($q2) {
                  $q2->whereIn('criticality', ['high', 'critical'])
                     ->where('is_documented', false);
              });
        });
    }
}

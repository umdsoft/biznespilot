<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use BelongsToBusiness, SoftDeletes, HasUuid;

    /**
     * Yo'qotilgan lid sabablari
     */
    public const LOST_REASONS = [
        'price' => 'Narx qimmat',
        'competitor' => 'Raqobatchini tanladi',
        'no_budget' => 'Byudjet yo\'q',
        'no_need' => 'Ehtiyoj yo\'q',
        'no_response' => 'Javob bermadi',
        'wrong_contact' => 'Noto\'g\'ri kontakt',
        'low_quality' => 'Sifatsiz lid',
        'timing' => 'Vaqt mos kelmadi',
        'other' => 'Boshqa sabab',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'uuid',
        'business_id',
        'source_id',
        'assigned_to',
        'name',
        'email',
        'phone',
        'company',
        'status',
        'lost_reason',
        'lost_reason_details',
        'score',
        'estimated_value',
        'data',
        'notes',
        'last_contacted_at',
        'converted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'estimated_value' => 'decimal:2',
        'data' => 'array',
        'last_contacted_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    /**
     * Get the lead source for the lead.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    /**
     * Get the user assigned to the lead.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the customer converted from this lead.
     */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * Get the form submissions for this lead.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(LeadFormSubmission::class);
    }

    /**
     * Get the tasks for this lead.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scope: Filter by assigned operator.
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope: Unassigned leads.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }
}

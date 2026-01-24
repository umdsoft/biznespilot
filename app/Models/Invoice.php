<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * Invoice statuses.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'business_id',
        'subscription_id',
        'invoice_number',
        'type',
        'status',
        'amount',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'items',
        'notes',
        'due_date',
        'paid_at',
        'payment_method',
        'payment_reference',
        'billing_name',
        'billing_address',
        'billing_phone',
        'billing_email',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'items' => 'array',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            // Auto-calculate total
            $invoice->total_amount = ($invoice->amount ?? 0)
                + ($invoice->tax_amount ?? 0)
                - ($invoice->discount_amount ?? 0);

            // Generate invoice number if not set
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            }
        });

        static::updating(function ($invoice) {
            // Recalculate total
            $invoice->total_amount = ($invoice->amount ?? 0)
                + ($invoice->tax_amount ?? 0)
                - ($invoice->discount_amount ?? 0);
        });
    }

    /**
     * Get the business that owns the invoice.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the subscription for the invoice.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the payments for the invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope for pending invoices.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('due_date', '<', now());
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->due_date
            && $this->due_date->isPast();
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(string $paymentMethod = null, string $reference = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'payment_reference' => $reference,
        ]);
    }

    /**
     * Get formatted invoice number.
     */
    public function getFormattedNumber(): string
    {
        return $this->invoice_number;
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Qoralama',
            self::STATUS_PENDING => 'Kutilmoqda',
            self::STATUS_PAID => 'To\'langan',
            self::STATUS_OVERDUE => 'Muddati o\'tgan',
            self::STATUS_CANCELLED => 'Bekor qilingan',
            self::STATUS_REFUNDED => 'Qaytarilgan',
            default => $this->status,
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING => 'yellow',
            self::STATUS_PAID => 'green',
            self::STATUS_OVERDUE => 'red',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_REFUNDED => 'blue',
            default => 'gray',
        };
    }
}

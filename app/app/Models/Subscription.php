<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'license_id', 'plan_id', 'status', 'provider', 'provider_subscription_id',
        'provider_customer_id', 'trial_ends_at', 'current_period_start',
        'current_period_end', 'canceled_at', 'ended_at', 'amount', 'currency',
        'payment_method', 'metadata',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'ended_at' => 'datetime',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Status checks
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing' && $this->trial_ends_at?->isFuture();
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled' || $this->canceled_at !== null;
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function isValid(): bool
    {
        return in_array($this->status, ['active', 'trialing']) &&
               ($this->current_period_end === null || $this->current_period_end->isFuture());
    }

    public function hasGracePeriod(): bool
    {
        if (!$this->isCanceled()) {
            return false;
        }

        return $this->current_period_end?->isFuture() ?? false;
    }

    public function daysUntilExpiry(): ?int
    {
        if (!$this->current_period_end) {
            return null;
        }

        return max(0, now()->diffInDays($this->current_period_end, false));
    }

    // Actions
    public function cancel(): void
    {
        $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);
    }

    public function resume(): void
    {
        if ($this->isCanceled() && $this->hasGracePeriod()) {
            $this->update([
                'status' => 'active',
                'canceled_at' => null,
            ]);
        }
    }

    public function markPastDue(): void
    {
        $this->update(['status' => 'past_due']);
    }

    public function markActive(): void
    {
        $this->update([
            'status' => 'active',
            'canceled_at' => null,
        ]);
    }

    public function end(): void
    {
        $this->update([
            'status' => 'canceled',
            'ended_at' => now(),
        ]);

        // Also suspend the license
        $this->license?->suspend('Abonnement terminé');
    }

    public function renewPeriod(\DateTime $start, \DateTime $end): void
    {
        $this->update([
            'current_period_start' => $start,
            'current_period_end' => $end,
            'status' => 'active',
        ]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiring($query, int $days = 7)
    {
        return $query->where('status', 'active')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', now()->addDays($days));
    }

    public function scopePastDue($query)
    {
        return $query->where('status', 'past_due');
    }
}

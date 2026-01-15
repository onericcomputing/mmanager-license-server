<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'subscription_id', 'license_id', 'provider', 'provider_payment_id',
        'provider_invoice_id', 'amount', 'tax', 'total', 'currency', 'status',
        'payment_method', 'description', 'receipt_url', 'invoice_pdf',
        'refunded_amount', 'refunded_at', 'refund_reason', 'metadata', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded' || $this->refunded_amount > 0;
    }

    public function isPartiallyRefunded(): bool
    {
        return $this->refunded_amount > 0 && $this->refunded_amount < $this->total;
    }

    public function refund(float $amount = null, string $reason = null): void
    {
        $refundAmount = $amount ?? $this->total;

        $this->update([
            'refunded_amount' => $this->refunded_amount + $refundAmount,
            'refunded_at' => now(),
            'refund_reason' => $reason,
            'status' => $refundAmount >= $this->total ? 'refunded' : $this->status,
        ]);
    }

    public function getFormattedAmountAttribute(): string
    {
        $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];
        $symbol = $symbols[$this->currency] ?? $this->currency;

        return number_format($this->total, 2, ',', ' ') . ' ' . $symbol;
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'succeeded');
    }

    public function scopeForPeriod($query, $start, $end)
    {
        return $query->whereBetween('paid_at', [$start, $end]);
    }
}

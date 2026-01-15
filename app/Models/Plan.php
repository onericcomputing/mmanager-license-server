<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'interval', 'interval_count',
        'price', 'currency', 'stripe_price_id', 'stripe_product_id',
        'paypal_plan_id', 'pdf_limit', 'api_limit', 'features',
        'is_active', 'is_featured', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];
        $symbol = $symbols[$this->currency] ?? $this->currency;

        $intervalLabel = match ($this->interval) {
            'month' => $this->interval_count === 1 ? '/mois' : "/{$this->interval_count} mois",
            'year' => $this->interval_count === 1 ? '/an' : "/{$this->interval_count} ans",
            default => '',
        };

        return number_format($this->price, 2, ',', ' ') . ' ' . $symbol . $intervalLabel;
    }

    public function getIntervalLabelAttribute(): string
    {
        return match ($this->interval) {
            'month' => $this->interval_count === 1 ? 'Mensuel' : "{$this->interval_count} mois",
            'year' => $this->interval_count === 1 ? 'Annuel' : "{$this->interval_count} ans",
            default => $this->interval,
        };
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }
}

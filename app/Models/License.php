<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class License extends Model
{
    protected $fillable = [
        'purchase_code', 'buyer', 'buyer_email', 'license_type',
        'purchase_date', 'support_until', 'item_id',
        'domain', 'domain_hash', 'status', 'revoke_reason', 'revoked_at',
        'verification_count', 'last_verified_at', 'last_ip', 'last_version',
        'pdf_count', 'last_pdf_at', 'envato_data', 'envato_checked_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'support_until' => 'date',
        'revoked_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'last_pdf_at' => 'datetime',
        'envato_checked_at' => 'datetime',
        'envato_data' => 'array',
    ];

    protected $hidden = ['purchase_code', 'envato_data'];

    public function logs(): HasMany
    {
        return $this->hasMany(LicenseLog::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trialing'])
            ->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($q) {
                $q->whereNull('current_period_end')
                  ->orWhere('current_period_end', '>', now());
            })
            ->exists();
    }

    public function isValid(): bool
    {
        return $this->status === 'active';
    }

    public function isRevoked(): bool
    {
        return in_array($this->status, ['revoked', 'suspended']);
    }

    public function matchesDomain(string $domain): bool
    {
        if (empty($this->domain)) return true;
        return $this->normalizeDomain($domain) === $this->normalizeDomain($this->domain);
    }

    public function normalizeDomain(string $domain): string
    {
        return preg_replace('/^www\./', '', strtolower(trim($domain)));
    }

    public function bindDomain(string $domain): void
    {
        $normalized = $this->normalizeDomain($domain);
        $this->update([
            'domain' => $normalized,
            'domain_hash' => hash('sha256', $normalized),
        ]);
    }

    public function revoke(string $reason = null): void
    {
        $this->update([
            'status' => 'revoked',
            'revoke_reason' => $reason,
            'revoked_at' => now(),
        ]);
        $this->log('revoke', 'success', null, ['reason' => $reason]);
    }

    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'revoke_reason' => $reason,
            'revoked_at' => now(),
        ]);
        $this->log('suspend', 'success', null, ['reason' => $reason]);
    }

    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'revoke_reason' => null,
            'revoked_at' => null,
        ]);
        $this->log('reactivate', 'success');
    }

    public function resetDomain(): void
    {
        $oldDomain = $this->domain;
        $this->update(['domain' => null, 'domain_hash' => null]);
        $this->log('domain_reset', 'success', null, ['old_domain' => $oldDomain]);
    }

    public function recordVerification(string $ip, ?string $version): void
    {
        $this->increment('verification_count');
        $this->update([
            'last_verified_at' => now(),
            'last_ip' => $ip,
            'last_version' => $version,
        ]);
    }

    public function recordPdf(): void
    {
        $this->increment('pdf_count');
        $this->update(['last_pdf_at' => now()]);
    }

    public function log(string $action, string $status = 'success', ?string $reason = null, array $meta = []): void
    {
        LicenseLog::create([
            'license_id' => $this->id,
            'purchase_code' => $this->purchase_code,
            'domain' => $this->domain,
            'ip_address' => request()->ip(),
            'action' => $action,
            'status' => $status,
            'failure_reason' => $reason,
            'version' => $this->last_version,
            'metadata' => $meta,
        ]);
    }

    public function getMaskedCodeAttribute(): string
    {
        $parts = explode('-', $this->purchase_code);
        return $parts[0] . '-****-****-****-' . substr(end($parts), -4);
    }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeRevoked($q) { return $q->whereIn('status', ['revoked', 'suspended']); }
}

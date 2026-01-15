<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'license_id', 'purchase_code', 'domain', 'ip_address',
        'action', 'status', 'failure_reason', 'version', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}

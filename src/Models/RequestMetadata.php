<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RequestMetadata extends Model
{
    protected $fillable = [
        'transaction_id',
        'ip_address',
        'user_agent',
        'referer',
        'country_code',
        'country_name',
        'region',
        'city',
        'latitude',
        'longitude',
        'isp',
        'device_type',
        'browser',
        'os',
        'device_fingerprint',
        'response_time_ms',
        'api_version',
        'is_vpn',
        'is_proxy',
        'is_mobile',
        'risk_score',
        'risk_reason',
        'custom_metadata',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'response_time_ms' => 'integer',
        'is_vpn' => 'boolean',
        'is_proxy' => 'boolean',
        'is_mobile' => 'boolean',
        'risk_score' => 'integer',
        'custom_metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('sisp.tables.request_metadata', 'sisp_request_metadata');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}

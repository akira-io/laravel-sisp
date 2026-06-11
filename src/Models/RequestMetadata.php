<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
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
])]
final class RequestMetadata extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    public function getTable(): string
    {
        return config('sisp.tables.request_metadata', 'sisp_request_metadata');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'response_time_ms' => 'integer',
            'is_vpn' => 'boolean',
            'is_proxy' => 'boolean',
            'is_mobile' => 'boolean',
            'risk_score' => 'integer',
            'custom_metadata' => 'array',
        ];
    }
}

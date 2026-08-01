<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycVerification extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'tier',
        'status',
        'provider',
        'provider_response',
        'rejection_reason',
        'bvn_encrypted',
        'nin_encrypted',
        'bvn_verified',
        'nin_verified',
        'verified_at',
    ];

    protected $casts = [
        'bvn_verified' => 'boolean',
        'nin_verified' => 'boolean',
        'provider_response' => 'array',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toApiArray(): array
    {
        return [
            'tier' => 'tier'.$this->tier,
            'status' => $this->status,
            'provider' => $this->provider,
            'bvn_verified' => $this->bvn_verified,
            'nin_verified' => $this->nin_verified,
            'verified_at' => $this->verified_at?->toDateTimeString(),
            'rejection_reason' => $this->rejection_reason,
            'provider_response' => $this->provider_response,
        ];
    }
}

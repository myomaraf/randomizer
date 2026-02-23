<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Raffle extends Model
{
    protected $fillable = [
        'raffle_id',
        'uuids_sha256',
        'count',
        'selected_uuid',
        'algorithm_version',
        'digest_sha256',
        'index_selected',
        'nonce_hex',
        'timestamp_utc',
    ];

    protected $casts = [
        'count' => 'integer',
        'index_selected' => 'integer',
        'timestamp_utc' => 'datetime',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(RaffleTicket::class);
    }
}

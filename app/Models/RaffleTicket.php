<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaffleTicket extends Model
{
    protected $fillable = [
        'raffle_id',
        'uuid',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }
}

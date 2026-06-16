<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransmissionRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transmission_id',
        'container_no',
        'payload',
        'status',
        'response_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function transmission(): BelongsTo
    {
        return $this->belongsTo(Transmission::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transmission extends Model
{
    protected $fillable = [
        'type',
        'status',
        'date_from',
        'date_to',
        'records_count',
        'success_count',
        'failed_count',
        'duplicate_count',
        'triggered_by',
        'sent_by',
        'response_summary',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'response_summary' => 'array',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(TransmissionRecord::class);
    }
}

<?php

namespace App\Models;

use App\Enums\GuestScanStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestScan extends Model
{
    protected $fillable = [
        'event_id', 'access_link_id', 'status', 'matches_count',
        'best_similarity', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => GuestScanStatusEnum::class,
            'matches_count' => 'integer',
            'best_similarity' => 'decimal:5',
        ];
    }

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function accessLink(): BelongsTo { return $this->belongsTo(AccessLink::class); }
    public function matches(): HasMany { return $this->hasMany(GuestScanMatch::class); }
    public function deliveries(): HasMany { return $this->hasMany(MediaDelivery::class); }
}

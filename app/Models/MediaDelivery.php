<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MediaDelivery extends Model
{
    protected $fillable = [
        'guest_scan_id', 'access_link_id', 'channel', 'recipient', 'status',
        'error_message', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function guestScan(): BelongsTo { return $this->belongsTo(GuestScan::class); }
    public function accessLink(): BelongsTo { return $this->belongsTo(AccessLink::class); }
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_delivery_items');
    }
}

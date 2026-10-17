<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestScanMatch extends Model
{
    protected $fillable = ['guest_scan_id', 'media_id', 'similarity', 'rank', 'face_index'];

    protected function casts(): array
    {
        return ['similarity' => 'decimal:5', 'rank' => 'integer', 'face_index' => 'integer'];
    }

    public function guestScan(): BelongsTo { return $this->belongsTo(GuestScan::class); }
    public function media(): BelongsTo { return $this->belongsTo(Media::class); }
}

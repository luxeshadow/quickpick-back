<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadBatch extends Model
{
    protected $fillable = [
        'event_id', 'created_by', 'status', 'expected_count', 'uploaded_count', 'failed_count',
    ];

    protected function casts(): array
    {
        return ['expected_count' => 'integer', 'uploaded_count' => 'integer', 'failed_count' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(UploadBatchItem::class);
    }
}

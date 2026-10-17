<?php

namespace App\Models;

use App\Enums\MediaTypeEnum;
use App\Enums\PhotoProcessingStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'event_id', 'media_type', 'storage_path', 'original_filename',
        'mime_type', 'file_size', 'width', 'height', 'processing_status',
        'faces_count', 'processing_error', 'processing_started_at', 'processed_at',
        'uploaded_at', 'processing_job_id', 'processing_attempt', 'model_version',
    ];

    protected function casts(): array
    {
        return [
            'processing_status' => PhotoProcessingStatusEnum::class,
            'media_type' => MediaTypeEnum::class,
            'file_size' => 'integer', 'width' => 'integer', 'height' => 'integer',
            'faces_count' => 'integer', 'processing_started_at' => 'datetime',
            'processing_attempt' => 'integer', 'processed_at' => 'datetime',
            'uploaded_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function guestScanMatches(): HasMany { return $this->hasMany(GuestScanMatch::class); }
    public function deliveries(): BelongsToMany
    {
        return $this->belongsToMany(MediaDelivery::class, 'media_delivery_items');
    }
}

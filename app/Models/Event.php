<?php

namespace App\Models;

use App\Enums\EventStatusEnum;
use App\Enums\EventMediaStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'location',
        'event_date',
        'status',
        'media_count',
        'media_status',
        'expected_media_count',
        'uploaded_media_count',
        'processed_media_count',
        'failed_media_count',
        'upload_started_at',
        'upload_completed_at',
        'processing_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'status' => EventStatusEnum::class,
            'media_status' => EventMediaStatusEnum::class,
            'expected_media_count' => 'integer',
            'uploaded_media_count' => 'integer',
            'processed_media_count' => 'integer',
            'failed_media_count' => 'integer',
            'upload_started_at' => 'datetime',
            'upload_completed_at' => 'datetime',
            'processing_completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function accessLinks(): HasMany
    {
        return $this->hasMany(AccessLink::class);
    }

    public function guestScans(): HasMany
    {
        return $this->hasMany(GuestScan::class);
    }

    public function uploadBatches(): HasMany
    {
        return $this->hasMany(UploadBatch::class);
    }

}

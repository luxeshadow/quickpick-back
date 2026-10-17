<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadBatchItem extends Model
{
    protected $fillable = ['upload_batch_id', 'media_id', 'filename', 'status', 'error'];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}

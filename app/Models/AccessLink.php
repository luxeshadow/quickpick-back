<?php

namespace App\Models;

use App\Enums\AccessLinkSourceEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessLink extends Model
{
    protected $appends = ['public_url'];

    protected $fillable = [
        'event_id', 'created_by', 'token', 'source',
        'expires_at', 'is_active', 'max_scans', 'scans_count', 'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => AccessLinkSourceEnum::class,
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'max_scans' => 'integer',
            'scans_count' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function guestScans(): HasMany { return $this->hasMany(GuestScan::class); }
    public function deliveries(): HasMany { return $this->hasMany(MediaDelivery::class); }

    protected function publicUrl(): Attribute
    {
        return Attribute::get(
            fn (): string => rtrim((string) config('app.frontend_url'), '/')
                .'/media?token='.rawurlencode($this->token)
        );
    }
}

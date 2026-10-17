<?php

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'subscription_id', 'provider', 'payment_method', 'amount',
        'currency', 'status', 'external_reference', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'status' => PaymentStatusEnum::class, 'paid_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
    public function invoice(): HasOne { return $this->hasOne(Invoice::class); }
}

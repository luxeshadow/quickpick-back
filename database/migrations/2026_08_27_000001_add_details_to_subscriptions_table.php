<?php

use App\Enums\SubscriptionStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->string('status')->default(SubscriptionStatusEnum::ACTIVE->value);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('cancelled_at')->nullable();

            $table->index(['user_id', 'status']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['subscription_plan_id']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['ends_at']);
            $table->dropColumn([
                'user_id',
                'subscription_plan_id',
                'status',
                'starts_at',
                'ends_at',
                'cancelled_at',
            ]);
        });
    }
};

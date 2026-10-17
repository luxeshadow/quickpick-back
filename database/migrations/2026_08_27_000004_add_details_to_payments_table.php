<?php

use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->string('provider');
            $table->string('payment_method');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('EUR');
            $table->string('status')->default(PaymentStatusEnum::PENDING->value);
            $table->string('external_reference')->nullable()->unique();
            $table->dateTime('paid_at')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['subscription_id']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropUnique(['external_reference']);
            $table->dropColumn([
                'user_id', 'subscription_id', 'provider', 'payment_method',
                'amount', 'currency', 'status', 'external_reference', 'paid_at',
            ]);
        });
    }
};

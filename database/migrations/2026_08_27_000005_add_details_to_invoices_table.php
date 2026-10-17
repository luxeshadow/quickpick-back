<?php

use App\Enums\InvoiceStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->char('currency', 3)->default('EUR');
            $table->string('status')->default(InvoiceStatusEnum::ISSUED->value);
            $table->dateTime('issued_at');
            $table->dateTime('due_at')->nullable();
            $table->dateTime('paid_at')->nullable();

            $table->index(['user_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['subscription_id']);
            $table->dropForeign(['payment_id']);
            $table->dropUnique(['payment_id']);
            $table->dropUnique(['invoice_number']);
            $table->dropIndex(['user_id', 'issued_at']);
            $table->dropColumn([
                'user_id', 'subscription_id', 'payment_id', 'invoice_number',
                'subtotal', 'tax', 'total', 'currency', 'status', 'issued_at',
                'due_at', 'paid_at',
            ]);
        });
    }
};

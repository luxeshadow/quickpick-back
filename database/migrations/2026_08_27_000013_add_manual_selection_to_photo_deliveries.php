<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photo_deliveries', function (Blueprint $table) {
            $table->foreignId('guest_scan_id')->nullable()->change();
            $table->foreignId('access_link_id')->nullable()->after('guest_scan_id')
                ->constrained()->cascadeOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
        });

        Schema::create('photo_delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['photo_delivery_id', 'photo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_delivery_items');
        Schema::table('photo_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('access_link_id');
            $table->dropColumn(['error_message', 'sent_at']);
            $table->foreignId('guest_scan_id')->nullable(false)->change();
        });
    }
};

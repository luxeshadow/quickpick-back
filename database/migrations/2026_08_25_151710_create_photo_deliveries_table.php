<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('photo_deliveries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('guest_scan_id')->constrained()->cascadeOnDelete();
        $table->string('channel');
        $table->string('recipient');
        $table->string('status')->default('pending');

        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photo_deliveries');
    }
};

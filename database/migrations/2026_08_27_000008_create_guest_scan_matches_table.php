<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_scan_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_scan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_id')->constrained()->cascadeOnDelete();
            $table->decimal('similarity', 6, 5);
            $table->unsignedInteger('rank');
            $table->unsignedInteger('face_index')->nullable();
            $table->timestamps();

            $table->unique(['guest_scan_id', 'photo_id']);
            $table->index(['guest_scan_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_scan_matches');
    }
};

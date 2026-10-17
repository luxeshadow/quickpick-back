<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\GuestScanStatusEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
        {
            Schema::create('guest_scans', function (Blueprint $table) {
        $table->id();

        // Event concerné
        $table->foreignId('event_id')->constrained()->cascadeOnDelete();
        $table->foreignId('access_link_id')->nullable()->constrained()->nullOnDelete();
        // Résultat du scan
        $table->string('status')->default(GuestScanStatusEnum::PROCESSING->value);

        $table->unsignedInteger('matches_count')->default(0);

        $table->decimal('best_similarity', 6, 5)->nullable();

        $table->text('error_message')->nullable();

        $table->timestamps();

        $table->index(['event_id', 'status']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_scans');
    }
};

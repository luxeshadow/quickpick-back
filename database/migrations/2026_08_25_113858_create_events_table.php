<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\EventStatusEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        // Event information
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('location')->nullable();
        $table->dateTime('event_date')->nullable();
        $table->string('status')->default(EventStatusEnum::ACTIVE->value);
        $table->unsignedInteger('photo_count')->default(0);
        $table->timestamps();
        // Indexes
        $table->index(['user_id', 'status']);
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

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
        Schema::create('photos', function (Blueprint $table) {
    $table->id();

    // Event auquel appartient la photo
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();

    // Supabase Storage
    $table->string('storage_path');

    // File information
    $table->string('original_filename');
    $table->string('mime_type')->nullable();
    $table->unsignedBigInteger('file_size')->nullable();
    $table->unsignedInteger('width')->nullable();
    $table->unsignedInteger('height')->nullable();

    // Vectorization status
    $table->string('processing_status')->default('pending');

    // Number of detected faces
    $table->unsignedInteger('faces_count')->default(0);

    // Error returned during processing
    $table->text('processing_error')->nullable();

    // Processing dates
    $table->timestamp('processing_started_at')->nullable();
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();

    $table->index(['event_id', 'processing_status']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};

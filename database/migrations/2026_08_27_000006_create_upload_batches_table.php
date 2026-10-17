<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('uploading');
            $table->unsignedInteger('expected_count');
            $table->unsignedInteger('uploaded_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('upload_completed_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index(['created_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_batches');
    }
};

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
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();
        });

        Schema::create('upload_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('filename');
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_batch_items');
        Schema::dropIfExists('upload_batches');
    }
};

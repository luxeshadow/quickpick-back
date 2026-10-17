<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->uuid('processing_job_id')->nullable()->unique();
            $table->unsignedInteger('processing_attempt')->default(0);
            $table->string('model_version', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropUnique(['processing_job_id']);
            $table->dropColumn(['processing_job_id', 'processing_attempt', 'model_version']);
        });
    }
};


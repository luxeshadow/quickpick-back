<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->foreignId('upload_batch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('access_links', function (Blueprint $table) {
            $table->foreignId('upload_batch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('access_links', function (Blueprint $table) {
            $table->dropConstrainedForeignId('upload_batch_id');
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('upload_batch_id');
        });
    }
};

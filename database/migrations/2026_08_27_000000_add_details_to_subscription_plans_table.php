<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('duration_days');
            $table->unsignedInteger('max_events')->nullable();
            $table->unsignedInteger('max_photos')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn([
                'name',
                'description',
                'price',
                'duration_days',
                'max_events',
                'max_photos',
                'is_active',
            ]);
        });
    }
};

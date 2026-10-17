<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->nullable()->change();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dateTime('ends_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->nullable(false)->change();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dateTime('ends_at')->nullable(false)->change();
        });
    }
};

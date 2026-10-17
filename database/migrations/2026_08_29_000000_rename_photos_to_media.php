<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('photos', 'media');

        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('photo_count', 'media_count');
        });

        Schema::table('guest_scan_matches', function (Blueprint $table) {
            $table->dropForeign(['photo_id']);
            $table->renameColumn('photo_id', 'media_id');
            $table->foreign('media_id')->references('id')->on('media')->cascadeOnDelete();
        });

        Schema::table('photo_delivery_items', function (Blueprint $table) {
            $table->dropForeign(['photo_delivery_id']);
            $table->dropForeign(['photo_id']);
            $table->renameColumn('photo_delivery_id', 'media_delivery_id');
            $table->renameColumn('photo_id', 'media_id');
        });

        Schema::rename('photo_delivery_items', 'media_delivery_items');
        Schema::rename('photo_deliveries', 'media_deliveries');

        Schema::table('media_delivery_items', function (Blueprint $table) {
            $table->foreign('media_delivery_id')->references('id')->on('media_deliveries')->cascadeOnDelete();
            $table->foreign('media_id')->references('id')->on('media')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('media_delivery_items', function (Blueprint $table) {
            $table->dropForeign(['media_delivery_id']);
            $table->dropForeign(['media_id']);
            $table->renameColumn('media_delivery_id', 'photo_delivery_id');
            $table->renameColumn('media_id', 'photo_id');
        });

        Schema::rename('media_delivery_items', 'photo_delivery_items');
        Schema::rename('media_deliveries', 'photo_deliveries');

        Schema::table('photo_delivery_items', function (Blueprint $table) {
            $table->foreign('photo_delivery_id')->references('id')->on('photo_deliveries')->cascadeOnDelete();
            $table->foreign('photo_id')->references('id')->on('media')->cascadeOnDelete();
        });

        Schema::table('guest_scan_matches', function (Blueprint $table) {
            $table->dropForeign(['media_id']);
            $table->renameColumn('media_id', 'photo_id');
            $table->foreign('photo_id')->references('id')->on('media')->cascadeOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('media_count', 'photo_count');
        });

        Schema::rename('media', 'photos');
    }
};

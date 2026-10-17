<?php

use App\Enums\MediaTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('accept_video')->default(false);
            $table->unsignedInteger('max_videos')->nullable();
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->string('media_type')->default(MediaTypeEnum::IMAGE->value)->index();
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropIndex(['media_type']);
            $table->dropColumn('media_type');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['accept_video', 'max_videos']);
        });
    }
};

<?php

use App\Enums\EventMediaStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('media_status')->default(EventMediaStatusEnum::EMPTY->value);
            $table->unsignedInteger('expected_media_count')->default(0);
            $table->unsignedInteger('uploaded_media_count')->default(0);
            $table->unsignedInteger('processed_media_count')->default(0);
            $table->unsignedInteger('failed_media_count')->default(0);
            $table->timestamp('upload_started_at')->nullable();
            $table->timestamp('upload_completed_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
        });

        foreach (DB::table('events')->get(['id']) as $event) {
            $media = DB::table('media')->where('event_id', $event->id);
            $total = (clone $media)->count();
            $uploaded = (clone $media)->whereNotNull('uploaded_at')->count();
            $processed = (clone $media)->whereIn('processing_status', ['processed', 'skipped'])->count();
            $failed = (clone $media)->where('processing_status', 'failed')->count();

            $status = match (true) {
                $total === 0 => EventMediaStatusEnum::EMPTY->value,
                $uploaded < $total => EventMediaStatusEnum::UPLOADING->value,
                $processed + $failed < $total => EventMediaStatusEnum::PROCESSING->value,
                $processed === 0 => EventMediaStatusEnum::FAILED->value,
                $failed > 0 => EventMediaStatusEnum::PARTIAL_FAILURE->value,
                default => EventMediaStatusEnum::COMPLETED->value,
            };

            DB::table('events')->where('id', $event->id)->update([
                'media_status' => $status,
                'expected_media_count' => $total,
                'uploaded_media_count' => $uploaded,
                'processed_media_count' => $processed,
                'failed_media_count' => $failed,
                'upload_started_at' => (clone $media)->min('created_at'),
                'upload_completed_at' => $uploaded === $total ? (clone $media)->max('uploaded_at') : null,
                'processing_completed_at' => $processed + $failed === $total
                    ? (clone $media)->max('processed_at')
                    : null,
            ]);
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE media DROP CONSTRAINT IF EXISTS photos_upload_batch_id_foreign');
            DB::statement('ALTER TABLE media DROP CONSTRAINT IF EXISTS media_upload_batch_id_foreign');
        } else {
            Schema::table('media', function (Blueprint $table) {
                $table->dropForeign(['upload_batch_id']);
            });
        }
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('upload_batch_id');
        });
        Schema::table('access_links', function (Blueprint $table) {
            $table->dropConstrainedForeignId('upload_batch_id');
        });
        Schema::dropIfExists('upload_batches');
    }

    public function down(): void
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
        });
        Schema::table('media', function (Blueprint $table) {
            $table->foreignId('upload_batch_id')->nullable()->constrained('upload_batches')->nullOnDelete();
        });
        Schema::table('access_links', function (Blueprint $table) {
            $table->foreignId('upload_batch_id')->nullable()->constrained('upload_batches')->nullOnDelete();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'media_status', 'expected_media_count', 'uploaded_media_count',
                'processed_media_count', 'failed_media_count', 'upload_started_at',
                'upload_completed_at', 'processing_completed_at',
            ]);
        });
    }
};

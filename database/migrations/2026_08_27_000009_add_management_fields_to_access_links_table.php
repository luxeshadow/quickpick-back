<?php

use App\Enums\AccessLinkSourceEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_links', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->default(AccessLinkSourceEnum::MANUAL->value);
            $table->unsignedInteger('max_scans')->nullable();
            $table->unsignedInteger('scans_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            $table->index(['created_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('access_links', function (Blueprint $table) {
            $table->dropIndex(['created_by', 'created_at']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['source', 'max_scans', 'scans_count', 'last_used_at']);
        });
    }
};

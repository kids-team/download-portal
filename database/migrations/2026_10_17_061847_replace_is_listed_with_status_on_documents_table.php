<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('copyright_notice');
            $table->index(['status', 'published_at']);
        });

        DB::table('documents')
            ->whereNotNull('published_at')
            ->where('is_listed', true)
            ->update(['status' => 'published']);

        DB::table('documents')
            ->where('is_listed', false)
            ->update(['status' => 'archived']);

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('is_listed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('is_listed')->default(true)->after('copyright_notice');
        });

        DB::table('documents')
            ->where('status', '!=', 'published')
            ->update(['is_listed' => false]);

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['status', 'published_at']);
            $table->dropColumn('status');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->uuid('source_page_archive_id')->nullable()->after('user_id');
            $table->foreign('source_page_archive_id')
                ->references('id')
                ->on('source_page_archives')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropForeign(['source_page_archive_id']);
            $table->dropColumn('source_page_archive_id');
        });
    }
};

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
        Schema::create('source_page_archives', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('source_url');
            $table->string('domain')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->string('content_hash', 64)->index();
            $table->unsignedBigInteger('content_bytes');
            $table->string('mime_type')->default('text/html');
            $table->string('compression')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_page_archives');
    }
};

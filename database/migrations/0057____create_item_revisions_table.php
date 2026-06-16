<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->uuid('user_id')->nullable();
            $table->unsignedInteger('revision_number');
            $table->string('event', 50);
            $table->string('summary', 255)->nullable();
            $table->string('snapshot_hash', 64);
            $table->json('snapshot');
            $table->json('meta')->nullable();
            $table->timestampsTz();

            $table->unique(['item_id', 'revision_number']);
            $table->index(['item_id', 'created_at']);
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_revisions');
    }
};

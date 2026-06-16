<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contribution_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('item_id')->nullable();
            $table->uuid('item_revision_id')->nullable();
            $table->string('event_type', 50);
            $table->integer('points');
            $table->string('status', 20)->default('awarded');
            $table->string('summary', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestampTz('awarded_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'created_at']);
            $table->index(['item_id', 'created_at']);
            $table->unique(['user_id', 'item_id', 'item_revision_id', 'event_type'], 'contribution_events_unique_award');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
            $table->foreign('item_revision_id')->references('id')->on('item_revisions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_events');
    }
};

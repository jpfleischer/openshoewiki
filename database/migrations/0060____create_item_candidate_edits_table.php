<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_candidate_edits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->uuid('base_revision_id');
            $table->uuid('user_id');
            $table->string('status', 40)->default('open');
            $table->string('title', 255)->nullable();
            $table->text('summary')->nullable();
            $table->json('proposed_snapshot');
            $table->json('diff_payload')->nullable();
            $table->string('risk_level', 20)->default('medium');
            $table->timestampTz('vote_window_ends_at');
            $table->timestampTz('review_started_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->uuid('resolver_user_id')->nullable();
            $table->uuid('applied_revision_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestampsTz();

            $table->index(['item_id', 'created_at']);
            $table->index(['status', 'vote_window_ends_at']);
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
            $table->foreign('base_revision_id')->references('id')->on('item_revisions')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('resolver_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('applied_revision_id')->references('id')->on('item_revisions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_candidate_edits');
    }
};

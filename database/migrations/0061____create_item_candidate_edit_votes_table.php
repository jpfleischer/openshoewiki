<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_candidate_edit_votes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('candidate_edit_id');
            $table->uuid('user_id');
            $table->smallInteger('vote');
            $table->text('reason')->nullable();
            $table->timestampsTz();

            $table->unique(['candidate_edit_id', 'user_id']);
            $table->index(['candidate_edit_id', 'vote']);
            $table->foreign('candidate_edit_id')->references('id')->on('item_candidate_edits')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_candidate_edit_votes');
    }
};

<?php

namespace App\Services\CandidateEdits;

use App\Models\ItemCandidateEdit;
use App\Models\ItemCandidateEditVote;
use App\Models\User;

class CandidateEditDecisionService
{
    public function __construct(
        protected CandidateEditApplyService $applyService,
    ) {
    }

    public function tallies(ItemCandidateEdit $candidateEdit): array
    {
        $approveVotes = $candidateEdit->votes()->where('vote', ItemCandidateEditVote::APPROVE)->count();
        $rejectVotes = $candidateEdit->votes()->where('vote', ItemCandidateEditVote::REJECT)->count();

        return [
            'approve' => $approveVotes,
            'reject' => $rejectVotes,
            'total' => $approveVotes + $rejectVotes,
            'margin' => $approveVotes - $rejectVotes,
        ];
    }

    public function thresholdFor(ItemCandidateEdit $candidateEdit): array
    {
        return match ($candidateEdit->risk_level) {
            ItemCandidateEdit::RISK_LOW => ['min_votes' => 3, 'min_margin' => 2, 'auto_apply' => false],
            ItemCandidateEdit::RISK_MEDIUM => ['min_votes' => 5, 'min_margin' => 3, 'auto_apply' => false],
            default => ['min_votes' => 5, 'min_margin' => 3, 'auto_apply' => false],
        };
    }

    public function shouldAutoApply(ItemCandidateEdit $candidateEdit): array
    {
        $tallies = $this->tallies($candidateEdit);
        $threshold = $this->thresholdFor($candidateEdit);

        return [
            'window_expired' => now()->greaterThanOrEqualTo($candidateEdit->vote_window_ends_at),
            'approvals_exceed_rejections' => $tallies['approve'] > $tallies['reject'],
            'min_votes_met' => $tallies['total'] >= $threshold['min_votes'],
            'min_margin_met' => $tallies['margin'] >= $threshold['min_margin'],
            'risk_allows_auto_apply' => $threshold['auto_apply'],
            'base_revision_clean' => $this->applyService->canApplyCleanly($candidateEdit),
            'tallies' => $tallies,
            'threshold' => $threshold,
        ];
    }

    public function process(ItemCandidateEdit $candidateEdit, ?User $actor = null): string
    {
        if (! $candidateEdit->isOpen()) {
            return $candidateEdit->status;
        }

        $decision = $this->shouldAutoApply($candidateEdit);

        if (! $decision['window_expired']) {
            return $candidateEdit->status;
        }

        if (! $decision['approvals_exceed_rejections'] || ! $decision['min_votes_met'] || ! $decision['min_margin_met']) {
            $candidateEdit->forceFill([
                'status' => ItemCandidateEdit::STATUS_REJECTED,
                'resolved_at' => now(),
                'resolver_user_id' => $actor?->getKey(),
                'meta' => array_merge($candidateEdit->meta ?? [], ['decision' => $decision]),
            ])->save();

            return $candidateEdit->status;
        }

        if (! $decision['risk_allows_auto_apply'] || ! $decision['base_revision_clean']) {
            $candidateEdit->forceFill([
                'status' => ItemCandidateEdit::STATUS_ACCEPTED_PENDING_STAFF,
                'resolved_at' => now(),
                'resolver_user_id' => $actor?->getKey(),
                'meta' => array_merge($candidateEdit->meta ?? [], ['decision' => $decision]),
            ])->save();

            return $candidateEdit->status;
        }

        $this->applyService->apply($candidateEdit, $actor);

        return ItemCandidateEdit::STATUS_APPLIED;
    }

    public function reject(ItemCandidateEdit $candidateEdit, User $actor, ?string $reason = null): void
    {
        $candidateEdit->forceFill([
            'status' => ItemCandidateEdit::STATUS_REJECTED,
            'resolved_at' => now(),
            'resolver_user_id' => $actor->getKey(),
            'meta' => array_merge($candidateEdit->meta ?? [], ['manual_rejection_reason' => $reason]),
        ])->save();
    }
}

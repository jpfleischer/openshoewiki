<?php

namespace App\Services\CandidateEdits;

use App\Models\ContributionEvent;
use App\Models\Item;
use App\Models\ItemCandidateEdit;
use App\Models\ItemRevision;
use App\Models\User;
use App\Services\Contributions\ContributionPointService;
use App\Services\Items\ItemRevisionService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CandidateEditApplyService
{
    public function __construct(
        protected ItemRevisionService $revisionService,
        protected ContributionPointService $contributionPointService,
    ) {
    }

    public function canApplyCleanly(ItemCandidateEdit $candidateEdit): bool
    {
        $latestRevision = $candidateEdit->item->revisions()->first();

        return $latestRevision !== null && $latestRevision->is($candidateEdit->baseRevision);
    }

    public function apply(ItemCandidateEdit $candidateEdit, ?User $actor = null): ItemRevision
    {
        return DB::transaction(function () use ($candidateEdit, $actor): ItemRevision {
            /** @var ItemCandidateEdit $lockedCandidateEdit */
            $lockedCandidateEdit = ItemCandidateEdit::query()
                ->whereKey($candidateEdit->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedCandidateEdit->isOpen() || $lockedCandidateEdit->status === ItemCandidateEdit::STATUS_ACCEPTED_PENDING_STAFF, 422);

            /** @var Item $item */
            $item = Item::query()->whereKey($lockedCandidateEdit->item_id)->lockForUpdate()->firstOrFail();
            $latestRevision = $item->revisions()->first();

            if ($latestRevision === null || ! $latestRevision->is($lockedCandidateEdit->baseRevision)) {
                abort(422, 'This candidate edit no longer applies cleanly because the live item has changed.');
            }

            $snapshot = $lockedCandidateEdit->proposed_snapshot;
            $itemData = Arr::only($snapshot['item'] ?? [], [
                'english_name',
                'foreign_name',
                'year',
                'product_number',
                'price',
                'currency',
                'notes',
                'brand_id',
                'category_id',
            ]);

            $item->forceFill($itemData);
            $item->save();

            $relationships = $snapshot['relationships'] ?? [];

            $item->categories()->sync(array_values($relationships['categories'] ?? []));
            $item->features()->sync(array_values($relationships['features'] ?? []));
            $item->colors()->sync(array_values($relationships['colors'] ?? []));
            $item->tags()->sync(array_values($relationships['tags'] ?? []));

            $attributeValues = collect($relationships['attributes'] ?? [])
                ->mapWithKeys(fn ($value, $attributeId): array => [
                    $attributeId => ['value' => $value],
                ])
                ->all();

            $item->attributes()->sync($attributeValues);

            $appliedRevision = $this->revisionService->capture(
                $item->fresh()->load(Item::FULLY_LOAD),
                $actor,
                'candidate_edit_applied',
                'Applied candidate edit',
                [
                    'source' => 'candidate-edit',
                    'candidate_edit_id' => $lockedCandidateEdit->getKey(),
                    'base_revision_id' => $lockedCandidateEdit->base_revision_id,
                ]
            );

            if ($appliedRevision === null) {
                abort(422, 'Applying this candidate edit did not produce a new revision.');
            }

            $lockedCandidateEdit->forceFill([
                'status' => ItemCandidateEdit::STATUS_APPLIED,
                'resolved_at' => now(),
                'resolver_user_id' => $actor?->getKey(),
                'applied_revision_id' => $appliedRevision->getKey(),
            ])->save();

            $this->contributionPointService->award(
                $lockedCandidateEdit->proposer,
                ContributionEvent::CANDIDATE_EDIT_APPLIED,
                $item,
                $appliedRevision,
                'Candidate edit was accepted and applied',
                [
                    'source' => 'candidate-edit',
                    'candidate_edit_id' => $lockedCandidateEdit->getKey(),
                ]
            );

            return $appliedRevision;
        });
    }
}

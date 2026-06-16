<?php

namespace App\Services\CandidateEdits;

use App\Models\Attribute;
use App\Models\Item;
use App\Models\ItemCandidateEdit;
use App\Models\ItemRevision;
use App\Models\User;
use App\Services\Items\ItemRevisionService;
use Illuminate\Support\Arr;

class CandidateEditService
{
    public function __construct(
        protected ItemRevisionService $revisionService,
        protected CandidateEditRiskService $riskService,
    ) {
    }

    public function ensureBaseRevision(Item $item): ItemRevision
    {
        $baseRevision = $item->revisions()->first();

        if ($baseRevision !== null) {
            return $baseRevision;
        }

        $captured = $this->revisionService->capture(
            $item,
            null,
            'backfilled',
            'Imported baseline for candidate edit review',
            ['source' => 'candidate-edit']
        );

        return $captured ?? $item->revisions()->firstOrFail();
    }

    public function draftFullSnapshot(Item $item, array $data): array
    {
        $snapshot = $this->revisionService->snapshot($item->fresh()->load(Item::FULLY_LOAD));

        $snapshot['item']['english_name'] = $data['english_name'];
        $snapshot['item']['foreign_name'] = $data['foreign_name'] ?: null;
        $snapshot['item']['year'] = filled($data['year']) ? (int) $data['year'] : null;
        $snapshot['item']['product_number'] = $data['product_number'] ?: null;
        $snapshot['item']['price'] = filled($data['price']) ? (float) $data['price'] : null;
        $snapshot['item']['currency'] = $data['currency'] ?: null;
        $snapshot['item']['notes'] = $data['notes'] ?: null;
        $snapshot['item']['brand_id'] = $data['brand_id'];
        $snapshot['item']['category_id'] = $data['category_ids'][0] ?? null;

        $snapshot['relationships']['categories'] = array_values($data['category_ids'] ?? []);
        $snapshot['relationships']['features'] = array_values($data['feature_ids'] ?? []);
        $snapshot['relationships']['colors'] = array_values($data['color_ids'] ?? []);
        $snapshot['relationships']['tags'] = array_values($data['tag_ids'] ?? []);
        $snapshot['relationships']['attributes'] = $this->normalizeAttributeValues($data['attributes'] ?? []);

        return $snapshot;
    }

    public function create(Item $item, User $user, array $data): ItemCandidateEdit
    {
        $baseRevision = $this->ensureBaseRevision($item);
        $proposedSnapshot = $this->draftFullSnapshot($item, $data);
        $fullDiff = $this->revisionService->diff($baseRevision->snapshot, $proposedSnapshot);
        $publicDiffRows = $this->revisionService->buildDiffRows(
            $this->revisionService->toPublicSnapshot($baseRevision->snapshot),
            $this->revisionService->toPublicSnapshot($proposedSnapshot),
            true
        );

        return ItemCandidateEdit::query()->create([
            'item_id' => $item->getKey(),
            'base_revision_id' => $baseRevision->getKey(),
            'user_id' => $user->getKey(),
            'status' => ItemCandidateEdit::STATUS_OPEN,
            'title' => $data['title'] ?: null,
            'summary' => $data['summary'] ?: null,
            'proposed_snapshot' => $proposedSnapshot,
            'diff_payload' => $publicDiffRows,
            'risk_level' => $this->riskService->classify(array_values(array_unique(array_column($fullDiff, 'path')))),
            'vote_window_ends_at' => now()->addDay(),
            'review_started_at' => now(),
            'meta' => [
                'full_diff_paths' => array_values(array_unique(array_column($fullDiff, 'path'))),
                'summary_fields' => array_values(array_unique(array_column($publicDiffRows, 'label'))),
            ],
        ]);
    }

    public function publicDiffRows(ItemCandidateEdit $candidateEdit): array
    {
        return $candidateEdit->diff_payload ?? [];
    }

    public function publicSnapshot(ItemCandidateEdit $candidateEdit): array
    {
        return $this->revisionService->toPublicSnapshot($candidateEdit->proposed_snapshot);
    }

    protected function normalizeAttributeValues(array $values): array
    {
        $values = collect($values)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): string => trim((string) $value))
            ->all();

        $validIds = Attribute::query()
            ->whereIn('id', array_keys($values))
            ->pluck('id')
            ->all();

        return Arr::only($values, $validIds);
    }
}

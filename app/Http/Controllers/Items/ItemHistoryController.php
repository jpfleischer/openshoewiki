<?php

namespace App\Http\Controllers\Items;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemRevision;
use App\Services\Items\ItemRevisionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ItemHistoryController extends Controller
{
    public function show(Request $request, Item $item, ItemRevisionService $revisionService)
    {
        if (! $item->published()) {
            $user = auth()->user();
            $allowed = $user && ($user->editor() || $item->user_id === $user->getKey());

            if (! $allowed) {
                abort(Response::HTTP_NOT_FOUND);
            }
        }

        $revisions = $item->revisions()->get();
        $selectedRevision = $revisions->firstWhere('id', $request->query('revision')) ?? $revisions->first();

        $comparisonRevision = null;

        if ($selectedRevision) {
            $selectedIndex = $revisions->search(fn (ItemRevision $revision): bool => $revision->is($selectedRevision));

            if ($selectedIndex !== false) {
                $comparisonRevision = $revisions->get($selectedIndex + 1);
            }
        }

        $selectedSnapshot = $selectedRevision
            ? $revisionService->toPublicSnapshot($selectedRevision->snapshot)
            : [];
        $comparisonSnapshot = $comparisonRevision
            ? $revisionService->toPublicSnapshot($comparisonRevision->snapshot)
            : [];
        $diffRows = $revisionService->buildDiffRows($comparisonSnapshot, $selectedSnapshot, true);

        return view('items.history', [
            'item' => $item->load(Item::FULLY_LOAD),
            'revisions' => $revisions,
            'selectedRevision' => $selectedRevision,
            'comparisonRevision' => $comparisonRevision,
            'selectedSnapshot' => $selectedSnapshot,
            'diffRows' => $diffRows,
            'revisionService' => $revisionService,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Items;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Feature;
use App\Models\Item;
use App\Models\ItemCandidateEdit;
use App\Models\ItemCandidateEditVote;
use App\Models\Tag;
use App\Services\CandidateEdits\CandidateEditApplyService;
use App\Services\CandidateEdits\CandidateEditDecisionService;
use App\Services\CandidateEdits\CandidateEditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemCandidateEditController extends Controller
{
    public function index(): View
    {
        $candidateEdits = ItemCandidateEdit::query()
            ->with(['item.brand', 'proposer'])
            ->withCount([
                'votes as approve_votes_count' => fn ($query) => $query->where('vote', ItemCandidateEditVote::APPROVE),
                'votes as reject_votes_count' => fn ($query) => $query->where('vote', ItemCandidateEditVote::REJECT),
            ])
            ->latest()
            ->paginate(20);

        return view('items.candidate-edits.index', [
            'candidateEdits' => $candidateEdits,
        ]);
    }

    public function show(ItemCandidateEdit $candidateEdit, CandidateEditService $candidateEditService): View
    {
        $candidateEdit->load([
            'item.brand',
            'proposer',
            'baseRevision',
            'votes.user',
        ])->loadCount([
            'votes as approve_votes_count' => fn ($query) => $query->where('vote', ItemCandidateEditVote::APPROVE),
            'votes as reject_votes_count' => fn ($query) => $query->where('vote', ItemCandidateEditVote::REJECT),
        ]);

        return view('items.candidate-edits.show', [
            'candidateEdit' => $candidateEdit,
            'diffRows' => $candidateEditService->publicDiffRows($candidateEdit),
            'currentVote' => auth()->check()
                ? $candidateEdit->votes->firstWhere('user_id', auth()->id())
                : null,
            'canModerate' => auth()->check() && auth()->user()->can('publish', $candidateEdit->item),
        ]);
    }

    public function create(Item $item): View
    {
        abort_unless(auth()->check(), 403);
        abort_unless($item->published(), 404);

        return view('items.candidate-edits.create', [
            'item' => $item->load(Item::FULLY_LOAD),
            'brands' => Brand::query()->get()->sortBy('name')->values(),
            'categories' => Category::query()->get()->sortBy('name')->values(),
            'features' => Feature::query()->get()->sortBy('name')->values(),
            'colors' => Color::query()->get()->sortBy('name')->values(),
            'tags' => Tag::query()->get()->sortBy('name')->values(),
            'attributes' => Attribute::query()->get()->sortBy('name')->values(),
            'currencies' => Item::CURRENCIES,
        ]);
    }

    public function store(Request $request, Item $item, CandidateEditService $candidateEditService): RedirectResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless($item->published(), 404);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:4000'],
            'english_name' => ['required', 'string', 'max:300'],
            'foreign_name' => ['nullable', 'string', 'max:300'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'product_number' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'brand_id' => ['required', 'uuid', 'exists:brands,id'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['uuid', 'exists:categories,id'],
            'feature_ids' => ['nullable', 'array'],
            'feature_ids.*' => ['uuid', 'exists:features,id'],
            'color_ids' => ['nullable', 'array'],
            'color_ids.*' => ['uuid', 'exists:colors,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],
            'attributes' => ['nullable', 'array'],
        ]);

        $candidateEdit = $candidateEditService->create($item, $request->user(), $data);

        if (($candidateEdit->diff_payload ?? []) === []) {
            $candidateEdit->delete();

            return back()
                ->withInput()
                ->withErrors(['summary' => 'Your candidate edit does not change any public fields yet.']);
        }

        return redirect()
            ->route('candidate-edits.show', $candidateEdit)
            ->with('status', 'Candidate edit submitted for community review.');
    }

    public function vote(Request $request, ItemCandidateEdit $candidateEdit): RedirectResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless($candidateEdit->isOpen(), 422);
        abort_if($candidateEdit->user_id === auth()->id(), 422, 'You cannot vote on your own candidate edit.');

        $data = $request->validate([
            'vote' => ['required', 'integer', 'in:-1,1'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        ItemCandidateEditVote::query()->updateOrCreate(
            [
                'candidate_edit_id' => $candidateEdit->getKey(),
                'user_id' => auth()->id(),
            ],
            [
                'vote' => (int) $data['vote'],
                'reason' => $data['reason'] ?? null,
            ]
        );

        return back()->with('status', $data['vote'] === 1 ? 'Approval vote recorded.' : 'Rejection vote recorded.');
    }

    public function apply(ItemCandidateEdit $candidateEdit, CandidateEditApplyService $applyService): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->can('publish', $candidateEdit->item), 403);

        $applyService->apply($candidateEdit, auth()->user());

        return back()->with('status', 'Candidate edit applied to the live item.');
    }

    public function reject(Request $request, ItemCandidateEdit $candidateEdit, CandidateEditDecisionService $decisionService): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->can('publish', $candidateEdit->item), 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $decisionService->reject($candidateEdit, auth()->user(), $data['reason'] ?? null);

        return back()->with('status', 'Candidate edit rejected.');
    }
}

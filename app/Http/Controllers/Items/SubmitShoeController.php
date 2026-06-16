<?php

namespace App\Http\Controllers\Items;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitShoeRequest;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Feature;
use App\Models\Image;
use App\Models\Item;
use App\Models\Tag;
use App\Models\User;
use App\Services\Contributions\ContributionPointService;
use App\Services\Items\ItemRevisionService;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SubmitShoeController extends Controller
{
    public function index()
    {
        return view('items.submissions', [
            'items' => auth()->user()
                ->items()
                ->with(Item::PARTIAL_LOAD)
                ->orderByDesc('created_at')
                ->paginate(12),
        ]);
    }

    public function create()
    {
        return view('items.submit', [
            'attributes' => Attribute::query()->get()->sortBy('name')->values(),
            'brands' => Brand::query()->get()->sortBy('name')->values(),
            'categories' => Category::query()->get()->sortBy('name')->values(),
            'features' => Feature::query()->get()->sortBy('name')->values(),
            'colors' => Color::query()->get()->sortBy('name')->values(),
            'tags' => Tag::query()->get()->sortBy('name')->values(),
            'currencies' => Item::CURRENCIES,
        ]);
    }

    public function store(SubmitShoeRequest $request): RedirectResponse
    {
        $item = DB::transaction(function () use ($request): Item {
            $user = $request->user();
            $brand = Brand::query()->findOrFail($request->input('brand_id'));
            $categoryIds = array_values(array_filter($request->input('category_ids', [])));

            $mainImage = $request->file('image')
                ? Image::from($request->file('image'))
                : null;

            $galleryImages = collect($request->file('images', []))
                ->map(fn ($file) => Image::from($file))
                ->map(fn (Image $image): array => [
                    'key' => bin2hex(random_bytes(8)),
                    'layout' => 'image',
                    'attributes' => [
                        'image' => 'images/' . $image->filename,
                    ],
                ])
                ->all();

            $item = new Item($request->safe()->only([
                'english_name',
                'foreign_name',
                'year',
                'product_number',
                'price',
                'currency',
                'notes',
            ]));

            $item->brand()->associate($brand);
            $item->category_id = $categoryIds[0] ?? null;
            $item->submitter()->associate($user);
            $item->internal_notes = 'Submitted via the public shoe submission form.';
            $item->image = $mainImage ? 'images/' . $mainImage->filename : null;
            $item->images = $galleryImages;
            $item->status = $user->junior() ? Item::DRAFT : Item::PENDING;
            $item->save();

            $item->categories()->sync($categoryIds);
            $item->features()->sync(array_values(array_filter($request->input('feature_ids', []))));
            $item->colors()->sync(array_values(array_filter($request->input('color_ids', []))));
            $item->tags()->sync(array_values(array_filter($request->input('tag_ids', []))));

            $attributeValues = collect($request->input('attributes', []))
                ->filter(fn ($value): bool => filled($value))
                ->mapWithKeys(fn ($value, $attributeId): array => [
                    $attributeId => ['value' => $value],
                ])
                ->all();

            $item->attributes()->sync($attributeValues);

            $revision = app(ItemRevisionService::class)->capture(
                $item,
                $user,
                'created',
                'Submitted via the public shoe submission form',
                ['source' => 'public-submit']
            );

            if ($revision !== null) {
                app(ContributionPointService::class)->awardForItemCreation(
                    $user,
                    $item,
                    $revision,
                    ['source' => 'public-submit']
                );
            }

            if ($user->junior()) {
                $item->publish($user);
            }

            return $item;
        });

        return redirect()
            ->route('submit.thanks', $item)
            ->with('status', $request->user()->junior()
                ? 'Your shoe submission has been published.'
                : 'Your shoe submission has been saved and is now pending review.');
    }

    public function thanks(Item $item)
    {
        abort_unless($this->canAccessSubmission($item, auth()->user()), Response::HTTP_NOT_FOUND);

        return view('items.submission-thanks', [
            'item' => $item->load(Item::FULLY_LOAD),
        ]);
    }

    protected function canAccessSubmission(Item $item, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->junior()) {
            return true;
        }

        return $item->user_id === $user->getKey();
    }
}

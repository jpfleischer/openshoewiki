<?php

namespace App\Services\Items;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Feature;
use App\Models\Item;
use App\Models\ItemRevision;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use NumberFormatter;
use Illuminate\Support\Facades\DB;

class ItemRevisionService
{
    public function capture(Item $item, ?User $actor = null, string $event = 'updated', ?string $summary = null, array $meta = []): ?ItemRevision
    {
        return DB::transaction(function () use ($item, $actor, $event, $summary, $meta): ?ItemRevision {
            $snapshot = $this->snapshot($item->fresh());
            $snapshotHash = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $latestRevision = ItemRevision::query()
                ->where('item_id', $item->getKey())
                ->orderByDesc('revision_number')
                ->lockForUpdate()
                ->first();

            if ($latestRevision?->snapshot_hash === $snapshotHash) {
                return null;
            }

            return ItemRevision::query()->create([
                'item_id' => $item->getKey(),
                'user_id' => $actor?->getKey(),
                'revision_number' => ($latestRevision?->revision_number ?? 0) + 1,
                'event' => $event,
                'summary' => $summary,
                'snapshot_hash' => $snapshotHash,
                'snapshot' => $snapshot,
                'meta' => $meta,
            ]);
        });
    }

    public function snapshot(Item $item): array
    {
        $item->loadMissing([
            'categories:id',
            'features:id',
            'colors:id',
            'tags:id',
            'attributes:id',
        ]);

        $attributeValues = $item->attributes
            ->sortBy('id')
            ->mapWithKeys(fn ($attribute): array => [
                $attribute->id => (string) $attribute->pivot->value,
            ])
            ->all();

        ksort($attributeValues);

        return [
            'item' => [
                'slug' => $item->slug,
                'english_name' => $item->english_name,
                'foreign_name' => $item->foreign_name,
                'year' => $item->year,
                'product_number' => $item->product_number,
                'price' => $item->price,
                'currency' => $item->currency,
                'notes' => $item->notes,
                'internal_notes' => $item->internal_notes,
                'image' => $item->image,
                'images' => $item->images,
                'status' => $item->status,
                'brand_id' => $item->brand_id,
                'category_id' => $item->category_id,
                'user_id' => $item->user_id,
                'publisher_id' => $item->publisher_id,
                'published_at' => optional($item->published_at)->toIso8601String(),
            ],
            'relationships' => [
                'categories' => $item->categories->pluck('id')->sort()->values()->all(),
                'features' => $item->features->pluck('id')->sort()->values()->all(),
                'colors' => $item->colors->pluck('id')->sort()->values()->all(),
                'tags' => $item->tags->pluck('id')->sort()->values()->all(),
                'attributes' => $attributeValues,
            ],
        ];
    }

    public function restore(ItemRevision $revision, ?User $actor = null): ?ItemRevision
    {
        /** @var Item $item */
        $item = $revision->item()->firstOrFail();

        return DB::transaction(function () use ($revision, $actor, $item): ?ItemRevision {
            /** @var Item $lockedItem */
            $lockedItem = Item::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $targetSnapshot = $revision->snapshot;
            $currentSnapshot = $this->snapshot($lockedItem->fresh());

            if ($this->snapshotHash($currentSnapshot) === $this->snapshotHash($targetSnapshot)) {
                return null;
            }

            $itemData = Arr::only($targetSnapshot['item'] ?? [], [
                'slug',
                'english_name',
                'foreign_name',
                'year',
                'product_number',
                'price',
                'currency',
                'notes',
                'internal_notes',
                'image',
                'images',
                'status',
                'brand_id',
                'category_id',
                'user_id',
                'publisher_id',
                'published_at',
            ]);

            $lockedItem->forceFill($itemData);
            $lockedItem->save();

            $relationships = $targetSnapshot['relationships'] ?? [];

            $lockedItem->categories()->sync(array_values($relationships['categories'] ?? []));
            $lockedItem->features()->sync(array_values($relationships['features'] ?? []));
            $lockedItem->colors()->sync(array_values($relationships['colors'] ?? []));
            $lockedItem->tags()->sync(array_values($relationships['tags'] ?? []));

            $attributeValues = collect($relationships['attributes'] ?? [])
                ->mapWithKeys(fn ($value, $attributeId): array => [
                    $attributeId => ['value' => $value],
                ])
                ->all();

            $lockedItem->attributes()->sync($attributeValues);

            return $this->capture(
                $lockedItem->fresh(),
                $actor,
                'restored',
                'Restored from revision #'.$revision->revision_number,
                [
                    'source' => 'revision-restore',
                    'restored_from_revision_id' => $revision->getKey(),
                    'restored_from_revision_number' => $revision->revision_number,
                ]
            );
        });
    }

    public function diff(array $fromSnapshot, array $toSnapshot): array
    {
        $from = $this->flattenSnapshot($fromSnapshot);
        $to = $this->flattenSnapshot($toSnapshot);
        $paths = array_unique([...array_keys($from), ...array_keys($to)]);
        sort($paths);

        $changes = [];

        foreach ($paths as $path) {
            $fromValue = $this->normalizeDiffValue($path, $from[$path] ?? null);
            $toValue = $this->normalizeDiffValue($path, $to[$path] ?? null);

            if ($fromValue === $toValue) {
                continue;
            }

            $changes[] = [
                'path' => $path,
                'from' => $fromValue,
                'to' => $toValue,
            ];
        }

        return $changes;
    }

    public function buildDiffRows(array $fromSnapshot, array $toSnapshot, bool $public = false): array
    {
        return collect($this->diff($fromSnapshot, $toSnapshot))
            ->map(function (array $change) use ($public): array {
                $from = $change['from'];
                $to = $change['to'];
                $removedLines = [];
                $addedLines = [];

                if (is_string($from) || is_string($to)) {
                    ['removed' => $removedLines, 'added' => $addedLines] = $this->buildLineDiff(
                        $from === null ? [] : preg_split("/\r\n|\n|\r/", (string) $from),
                        $to === null ? [] : preg_split("/\r\n|\n|\r/", (string) $to),
                    );
                }

                return [
                    'path' => $change['path'],
                    'label' => $public ? $this->publicFieldLabel($change['path']) : $this->adminFieldLabel($change['path']),
                    'from' => $from,
                    'to' => $to,
                    'removed_lines' => $removedLines,
                    'added_lines' => $addedLines,
                ];
            })
            ->values()
            ->all();
    }

    public function toPublicSnapshot(array $snapshot): array
    {
        $item = $snapshot['item'] ?? [];
        $relationships = $snapshot['relationships'] ?? [];

        return [
            'pair_name' => $item['english_name'] ?? null,
            'original_name' => $item['foreign_name'] ?? null,
            'brand' => $this->lookupName(Brand::class, $item['brand_id'] ?? null),
            'release_year' => $item['year'] ?? null,
            'style_code' => $item['product_number'] ?? null,
            'price' => $this->formatSnapshotPrice($item['price'] ?? null, $item['currency'] ?? null),
            'categories' => $this->lookupNames(Category::class, $relationships['categories'] ?? []),
            'features' => $this->lookupNames(Feature::class, $relationships['features'] ?? []),
            'colorways' => $this->lookupNames(Color::class, $relationships['colors'] ?? []),
            'tags' => $this->lookupNames(Tag::class, $relationships['tags'] ?? []),
            'specifications' => $this->formatAttributeValues($relationships['attributes'] ?? []),
            'notes' => $item['notes'] ?? null,
        ];
    }

    public function publicFieldLabel(string $path): string
    {
        return match ($path) {
            'pair_name' => 'Pair Name',
            'original_name' => 'Original / Alternate Name',
            'brand' => 'Brand',
            'release_year' => 'Release Year',
            'style_code' => 'Style Code / SKU',
            'price' => 'Price',
            'categories' => 'Categories',
            'features' => 'Features',
            'colorways' => 'Colorways',
            'tags' => 'Tags',
            'specifications' => 'Technical Specs',
            'notes' => 'Notes',
            default => str_replace('_', ' ', ucfirst($path)),
        };
    }

    public function adminFieldLabel(string $path): string
    {
        return match ($path) {
            'item.english_name' => 'Pair Name',
            'item.foreign_name' => 'Original / Alternate Name',
            'item.year' => 'Release Year',
            'item.product_number' => 'Style Code / SKU',
            'item.price' => 'Price',
            'item.currency' => 'Currency',
            'item.notes' => 'Notes',
            'item.internal_notes' => 'Internal Notes',
            'item.brand_id' => 'Brand',
            'relationships.categories' => 'Categories',
            'relationships.features' => 'Features',
            'relationships.colors' => 'Colorways',
            'relationships.tags' => 'Tags',
            'relationships.attributes' => 'Technical Specs',
            default => str($path)->replace(['item.', 'relationships.'], '')->replace('_', ' ')->title()->toString(),
        };
    }

    public function publicEventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Created',
            'updated' => 'Updated',
            'published' => 'Published',
            'unpublished' => 'Unpublished',
            'restored' => 'Restored',
            'status_changed' => 'Status Changed',
            'backfilled' => 'Imported Baseline',
            default => str($event)->replace('_', ' ')->title()->toString(),
        };
    }

    protected function flattenSnapshot(array $snapshot, string $prefix = ''): array
    {
        $flat = [];

        foreach ($snapshot as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                if ($this->isList($value)) {
                    $flat[$path] = json_encode(array_values($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    continue;
                }

                $flat += $this->flattenSnapshot($value, $path);
                continue;
            }

            $flat[$path] = $value;
        }

        return $flat;
    }

    protected function normalizeDiffValue(string $path, mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if (in_array($path, ['item.notes', 'item.internal_notes', 'notes'], true)) {
            return $this->normalizeRichTextForDiff($value);
        }

        return str_replace(["\r\n", "\r"], "\n", $value);
    }

    protected function normalizeRichTextForDiff(string $value): string
    {
        $value = str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $value);
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace("/\r\n|\r/", "\n", $value) ?? $value;
        $value = preg_replace("/([^\n])Imported extracted JSON:/", "$1\n\nImported extracted JSON:", $value) ?? $value;
        $value = preg_replace("/[ \t]+/", ' ', $value) ?? $value;
        $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;
        $value = collect(explode("\n", $value))
            ->map(fn (string $line): string => trim($line))
            ->implode("\n");

        return trim($value);
    }

    protected function buildLineDiff(array $fromLines, array $toLines): array
    {
        $fromLines = array_values(array_filter($fromLines, fn ($line): bool => $line !== null));
        $toLines = array_values(array_filter($toLines, fn ($line): bool => $line !== null));

        if ($fromLines === $toLines) {
            return ['removed' => [], 'added' => []];
        }

        $lcs = array_fill(0, count($fromLines) + 1, array_fill(0, count($toLines) + 1, 0));

        for ($i = count($fromLines) - 1; $i >= 0; $i--) {
            for ($j = count($toLines) - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $fromLines[$i] === $toLines[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $removed = [];
        $added = [];
        $i = 0;
        $j = 0;

        while ($i < count($fromLines) && $j < count($toLines)) {
            if ($fromLines[$i] === $toLines[$j]) {
                $i++;
                $j++;
                continue;
            }

            if ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $removed[] = $fromLines[$i];
                $i++;
                continue;
            }

            $added[] = $toLines[$j];
            $j++;
        }

        while ($i < count($fromLines)) {
            $removed[] = $fromLines[$i];
            $i++;
        }

        while ($j < count($toLines)) {
            $added[] = $toLines[$j];
            $j++;
        }

        return [
            'removed' => array_values(array_filter($removed, fn (string $line): bool => filled(trim($line)))),
            'added' => array_values(array_filter($added, fn (string $line): bool => filled(trim($line)))),
        ];
    }

    protected function snapshotHash(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function lookupName(string $modelClass, mixed $id): ?string
    {
        if (! filled($id)) {
            return null;
        }

        /** @var \Illuminate\Database\Eloquent\Model|null $model */
        $model = $modelClass::query()->find($id);

        return $model?->name;
    }

    protected function lookupNames(string $modelClass, array $ids): array
    {
        $ids = array_values(array_filter($ids));

        if ($ids === []) {
            return [];
        }

        return $modelClass::query()
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($model) => $model->name)
            ->pluck('name')
            ->values()
            ->all();
    }

    protected function formatAttributeValues(array $attributeValues): array
    {
        $attributeIds = array_keys($attributeValues);

        if ($attributeIds === []) {
            return [];
        }

        $names = Attribute::query()
            ->whereIn('id', $attributeIds)
            ->get()
            ->pluck('name', 'id');

        return collect($attributeValues)
            ->map(function ($value, $attributeId) use ($names): string {
                $label = $names[$attributeId] ?? $attributeId;

                return $label.': '.$value;
            })
            ->sort()
            ->values()
            ->all();
    }

    protected function formatSnapshotPrice(mixed $price, ?string $currency): ?string
    {
        if (! filled($price) || ! filled($currency)) {
            return null;
        }

        $numericPrice = in_array(strtolower($currency), ['jpy', 'krw', 'cny'], true)
            ? round((float) $price)
            : round((float) $price, 2);

        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($numericPrice, strtoupper($currency));
    }

    protected function isList(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }
}

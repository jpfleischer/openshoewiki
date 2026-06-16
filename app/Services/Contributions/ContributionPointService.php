<?php

namespace App\Services\Contributions;

use App\Models\ContributionEvent;
use App\Models\Item;
use App\Models\ItemRevision;
use App\Models\User;

class ContributionPointService
{
    public function awardForItemCreation(User $user, Item $item, ?ItemRevision $revision = null, array $meta = []): array
    {
        $imageCount = $this->countImages($item);
        $attributeCount = $item->attributes()->count();

        $events = [
            $this->award(
                $user,
                ContributionEvent::ITEM_CREATED,
                $item,
                $revision,
                'Created a new pair',
                $meta + [
                    'image_count' => $imageCount,
                    'attribute_count' => $attributeCount,
                ],
            ),
        ];

        if ($imageCount > 0) {
            $events[] = $this->award(
                $user,
                ContributionEvent::IMAGE_ADDED,
                $item,
                $revision,
                'Added product images on creation',
                $meta + ['image_count' => $imageCount],
            );
        }

        if ($attributeCount > 0) {
            $events[] = $this->award(
                $user,
                ContributionEvent::ATTRIBUTES_ADDED,
                $item,
                $revision,
                'Added structured specifications on creation',
                $meta + ['attribute_count' => $attributeCount],
            );
        }

        return array_values(array_filter($events));
    }

    public function awardForItemUpdate(User $user, Item $item, ItemRevision $revision, array $meta = []): ?ContributionEvent
    {
        return $this->award(
            $user,
            ContributionEvent::ITEM_UPDATED,
            $item,
            $revision,
            'Updated an existing pair',
            $meta,
        );
    }

    public function awardForItemPublish(User $user, Item $item, ?ItemRevision $revision = null, array $meta = []): ?ContributionEvent
    {
        return $this->award(
            $user,
            ContributionEvent::ITEM_PUBLISHED,
            $item,
            $revision,
            'Published a pair',
            $meta,
        );
    }

    public function award(
        User $user,
        string $eventType,
        ?Item $item = null,
        ?ItemRevision $revision = null,
        ?string $summary = null,
        array $meta = [],
        ?int $points = null,
    ): ?ContributionEvent {
        $points ??= $this->calculatePoints($eventType, $meta);

        if ($points <= 0) {
            return null;
        }

        return ContributionEvent::query()->firstOrCreate(
            [
                'user_id' => $user->getKey(),
                'item_id' => $item?->getKey(),
                'item_revision_id' => $revision?->getKey(),
                'event_type' => $eventType,
            ],
            [
                'points' => $points,
                'status' => ContributionEvent::STATUS_AWARDED,
                'summary' => $summary,
                'meta' => $meta,
                'awarded_at' => now(),
            ],
        );
    }

    public function calculatePoints(string $eventType, array $meta = []): int
    {
        return match ($eventType) {
            ContributionEvent::ITEM_CREATED => 10,
            ContributionEvent::ITEM_PUBLISHED => 25,
            ContributionEvent::ITEM_UPDATED => 3,
            ContributionEvent::IMAGE_ADDED => (int) (($meta['image_count'] ?? 0) > 0 ? 5 : 0),
            ContributionEvent::ATTRIBUTES_ADDED => min(((int) ($meta['attribute_count'] ?? 0)) * 2, 10),
            ContributionEvent::CANDIDATE_EDIT_APPLIED => 8,
            default => 0,
        };
    }

    protected function countImages(Item $item): int
    {
        $mainImageCount = filled($item->image) ? 1 : 0;
        $galleryCount = collect($item->images ?? [])
            ->filter(fn (array $image): bool => filled(data_get($image, 'attributes.image')))
            ->count();

        return $mainImageCount + $galleryCount;
    }
}

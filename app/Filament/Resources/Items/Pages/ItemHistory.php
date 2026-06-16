<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Resources\Items\ItemResource;
use App\Models\Item;
use App\Models\ItemRevision;
use App\Services\Items\ItemRevisionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class ItemHistory extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ItemResource::class;

    protected string $view = 'filament.resources.items.pages.item-history';

    protected static ?string $breadcrumb = 'History';

    public ?string $selectedRevisionId = null;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(auth()->user()?->can('viewRevisionHistory', $this->getRecord()), 403);

        $this->selectedRevisionId = $this->getRevisions()->first()?->getKey();
    }

    public function getTitle(): string
    {
        return 'Revision History';
    }

    public function getHeading(): string
    {
        return 'Revision History';
    }

    /**
     * @return Collection<int, ItemRevision>
     */
    public function getRevisions(): Collection
    {
        /** @var Item $record */
        $record = $this->getRecord();

        return $record->revisions()
            ->with('user:id,name,username')
            ->get();
    }

    public function getSelectedRevision(): ?ItemRevision
    {
        $revisions = $this->getRevisions();

        if ($this->selectedRevisionId !== null) {
            $selected = $revisions->firstWhere('id', $this->selectedRevisionId);

            if ($selected) {
                return $selected;
            }
        }

        return $revisions->first();
    }

    public function selectRevision(string $revisionId): void
    {
        $revision = $this->getRevisions()->firstWhere('id', $revisionId);

        if (! $revision) {
            return;
        }

        $this->selectedRevisionId = $revision->getKey();
    }

    public function getSelectedSnapshotJson(): string
    {
        $revision = $this->getSelectedRevision();

        if (! $revision) {
            return '{}';
        }

        return json_encode($revision->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function getLiveSnapshotJson(): string
    {
        /** @var Item $record */
        $record = $this->getRecord();

        return json_encode(
            app(ItemRevisionService::class)->snapshot($record->fresh()),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    public function getDiffRows(): array
    {
        $revision = $this->getSelectedRevision();

        if (! $revision) {
            return [];
        }

        $revisions = $this->getRevisions();
        $selectedIndex = $revisions->search(fn (ItemRevision $itemRevision): bool => $itemRevision->is($revision));
        $comparisonRevision = $selectedIndex === false ? null : $revisions->get($selectedIndex + 1);

        return app(ItemRevisionService::class)->buildDiffRows(
            $comparisonRevision?->snapshot ?? [],
            $revision->snapshot,
            false,
        );
    }

    public function restoreSelectedRevision(): void
    {
        $revision = $this->getSelectedRevision();

        if (! $revision) {
            Notification::make()
                ->title('No revision selected')
                ->danger()
                ->send();

            return;
        }

        abort_unless(auth()->user()?->can('restoreRevision', $this->getRecord()), 403);

        $restoredRevision = app(ItemRevisionService::class)->restore($revision, auth()->user());
        $this->record = $this->resolveRecord($this->getRecord()->getKey());
        $this->selectedRevisionId = $restoredRevision?->getKey() ?? $revision->getKey();

        Notification::make()
            ->title($restoredRevision ? 'Revision restored' : 'Item already matches this revision')
            ->success()
            ->send();
    }

    public function canRestoreSelectedRevision(): bool
    {
        $revision = $this->getSelectedRevision();

        return $revision !== null && (auth()->user()?->can('restoreRevision', $this->getRecord()) ?? false);
    }
}

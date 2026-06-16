<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Resources\Items\ItemResource;
use App\Models\Item;
use App\Services\Contributions\ContributionPointService;
use App\Services\Items\ItemRevisionService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageItems extends ManageRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => auth()->user()?->can('create', Item::class) ?? false),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Item $record */
        $record = $this->getRecord();

        return ItemResource::mutateItemFormDataBeforeFill($record, $data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $relationshipData = $this->extractRelationshipData($data);

        $record = new Item($data);
        $record->status = Item::DRAFT;
        $record->save();

        $this->syncRelationships($record, $relationshipData);
        $revision = app(ItemRevisionService::class)->capture(
            $record,
            auth()->user(),
            'created',
            'Created via Filament',
            ['source' => 'filament']
        );

        if ($revision !== null && auth()->user() !== null) {
            app(ContributionPointService::class)->awardForItemCreation(
                auth()->user(),
                $record,
                $revision,
                ['source' => 'filament']
            );
        }

        return $record;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Item $record */
        return ItemResource::updateItemRecord($record, $data);
    }
}

<?php

namespace App\Filament\Resources\CandidateEdits\Pages;

use App\Filament\Resources\CandidateEdits\CandidateEditResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCandidateEdits extends ManageRecords
{
    protected static string $resource = CandidateEditResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

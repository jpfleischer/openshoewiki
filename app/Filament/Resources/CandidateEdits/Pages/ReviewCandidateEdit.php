<?php

namespace App\Filament\Resources\CandidateEdits\Pages;

use App\Filament\Resources\CandidateEdits\CandidateEditResource;
use App\Models\ItemCandidateEdit;
use App\Services\CandidateEdits\CandidateEditApplyService;
use App\Services\CandidateEdits\CandidateEditDecisionService;
use App\Services\CandidateEdits\CandidateEditService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ReviewCandidateEdit extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CandidateEditResource::class;

    protected string $view = 'filament.resources.candidate-edits.pages.review-candidate-edit';

    protected static ?string $breadcrumb = 'Review';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(auth()->user()?->lolibrarian(), 403);
    }

    public function getTitle(): string
    {
        return 'Review Candidate Edit';
    }

    public function getHeading(): string
    {
        return 'Review Candidate Edit';
    }

    public function getRecord(): ItemCandidateEdit
    {
        /** @var ItemCandidateEdit $record */
        $record = $this->record;

        return $record;
    }

    public function getCandidateEdit(): ItemCandidateEdit
    {
        return $this->getRecord()->load([
            'item.brand',
            'item.categories',
            'proposer',
            'baseRevision',
            'appliedRevision',
            'resolver',
            'votes.user',
        ])->loadCount([
            'votes as approve_votes_count' => fn ($query) => $query->where('vote', 1),
            'votes as reject_votes_count' => fn ($query) => $query->where('vote', -1),
        ]);
    }

    public function getDiffRows(): array
    {
        return app(CandidateEditService::class)->publicDiffRows($this->getCandidateEdit());
    }

    public function getDecisionSnapshot(): array
    {
        return app(CandidateEditDecisionService::class)->shouldAutoApply($this->getCandidateEdit());
    }

    public function processNow(): void
    {
        $candidateEdit = $this->getCandidateEdit();
        $status = app(CandidateEditDecisionService::class)->process($candidateEdit, auth()->user());
        $this->record = $this->resolveRecord($candidateEdit->getKey());

        Notification::make()
            ->title("Candidate edit processed: {$status}")
            ->success()
            ->send();
    }

    public function applyNow(): void
    {
        $candidateEdit = $this->getCandidateEdit();
        app(CandidateEditApplyService::class)->apply($candidateEdit, auth()->user());
        $this->record = $this->resolveRecord($candidateEdit->getKey());

        Notification::make()
            ->title('Candidate edit applied')
            ->success()
            ->send();
    }

    public function rejectNow(): void
    {
        $candidateEdit = $this->getCandidateEdit();
        app(CandidateEditDecisionService::class)->reject($candidateEdit, auth()->user());
        $this->record = $this->resolveRecord($candidateEdit->getKey());

        Notification::make()
            ->title('Candidate edit rejected')
            ->success()
            ->send();
    }
}

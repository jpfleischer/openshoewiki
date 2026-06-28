<?php

namespace App\Filament\Resources\CandidateEdits;

use App\Filament\Resources\CandidateEdits\Pages\ManageCandidateEdits;
use App\Filament\Resources\CandidateEdits\Pages\ReviewCandidateEdit;
use App\Models\ItemCandidateEdit;
use App\Services\CandidateEdits\CandidateEditApplyService;
use App\Services\CandidateEdits\CandidateEditDecisionService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CandidateEditResource extends Resource
{
    protected static ?string $model = ItemCandidateEdit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Moderation';

    protected static ?string $navigationLabel = 'Candidate Edits';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['item.brand', 'proposer'])->withCount([
                'votes as approve_votes_count' => fn ($voteQuery) => $voteQuery->where('vote', 1),
                'votes as reject_votes_count' => fn ($voteQuery) => $voteQuery->where('vote', -1),
            ])->latest())
            ->columns([
                TextColumn::make('item.english_name')
                    ->label('Pair')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('item.brand.name')
                    ->label('Brand')
                    ->toggleable(),
                TextColumn::make('proposer.username')
                    ->label('Proposed By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state, ItemCandidateEdit $record): string => $record->statusLabel())
                    ->color(fn (string $state): string => match ($state) {
                        ItemCandidateEdit::STATUS_APPLIED => 'success',
                        ItemCandidateEdit::STATUS_REJECTED, ItemCandidateEdit::STATUS_CANCELLED => 'danger',
                        ItemCandidateEdit::STATUS_ACCEPTED_PENDING_STAFF => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('risk_level')
                    ->label('Risk')
                    ->badge()
                    ->formatStateUsing(fn (string $state, ItemCandidateEdit $record): string => $record->riskLabel())
                    ->color(fn (string $state): string => match ($state) {
                        ItemCandidateEdit::RISK_LOW => 'success',
                        ItemCandidateEdit::RISK_HIGH => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('approve_votes_count')
                    ->label('+ Votes')
                    ->sortable(),
                TextColumn::make('reject_votes_count')
                    ->label('- Votes')
                    ->sortable(),
                TextColumn::make('vote_window_ends_at')
                    ->label('Window Ends')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        ItemCandidateEdit::STATUS_OPEN => 'Open',
                        ItemCandidateEdit::STATUS_ACCEPTED_PENDING_STAFF => 'Accepted Pending Staff Review',
                        ItemCandidateEdit::STATUS_APPLIED => 'Applied',
                        ItemCandidateEdit::STATUS_REJECTED => 'Rejected',
                        ItemCandidateEdit::STATUS_CANCELLED => 'Cancelled',
                    ]),
                SelectFilter::make('risk_level')
                    ->label('Risk')
                    ->options([
                        ItemCandidateEdit::RISK_LOW => 'Low Risk',
                        ItemCandidateEdit::RISK_MEDIUM => 'Medium Risk',
                        ItemCandidateEdit::RISK_HIGH => 'High Risk',
                    ]),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Review')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (ItemCandidateEdit $record): string => static::getUrl('review', ['record' => $record])),
                Action::make('process_now')
                    ->label('Process Now')
                    ->icon(Heroicon::OutlinedBolt)
                    ->visible(fn (ItemCandidateEdit $record): bool => $record->status === ItemCandidateEdit::STATUS_OPEN)
                    ->requiresConfirmation()
                    ->action(function (ItemCandidateEdit $record): void {
                        app(CandidateEditDecisionService::class)->process($record, auth()->user());
                    }),
                Action::make('apply')
                    ->label('Apply')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (ItemCandidateEdit $record): bool => in_array($record->status, [
                        ItemCandidateEdit::STATUS_OPEN,
                        ItemCandidateEdit::STATUS_ACCEPTED_PENDING_STAFF,
                    ], true))
                    ->requiresConfirmation()
                    ->action(function (ItemCandidateEdit $record): void {
                        app(CandidateEditApplyService::class)->apply($record, auth()->user());
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (ItemCandidateEdit $record): bool => in_array($record->status, [
                        ItemCandidateEdit::STATUS_OPEN,
                        ItemCandidateEdit::STATUS_ACCEPTED_PENDING_STAFF,
                    ], true))
                    ->requiresConfirmation()
                    ->action(function (ItemCandidateEdit $record): void {
                        app(CandidateEditDecisionService::class)->reject($record, auth()->user());
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCandidateEdits::route('/'),
            'review' => ReviewCandidateEdit::route('/{record}/review'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->moderator() ?? false;
    }
}

<?php

namespace App\Services\Contributions;

use App\Models\ContributionEvent;
use App\Models\User;
use Illuminate\Support\Collection;

class ContributionLeaderboardService
{
    public function topContributors(int $limit = 10): Collection
    {
        return User::query()
            ->whereHas('contributionEvents', fn ($query) => $query->where('status', ContributionEvent::STATUS_AWARDED))
            ->withSum([
                'contributionEvents as contribution_points' => fn ($query) => $query->where('status', ContributionEvent::STATUS_AWARDED),
            ], 'points')
            ->withCount([
                'contributionEvents as contribution_count' => fn ($query) => $query->where('status', ContributionEvent::STATUS_AWARDED),
            ])
            ->withMax([
                'contributionEvents as last_contribution_at' => fn ($query) => $query->where('status', ContributionEvent::STATUS_AWARDED),
            ], 'awarded_at')
            ->orderByDesc('contribution_points')
            ->orderByDesc('contribution_count')
            ->orderBy('username')
            ->take($limit)
            ->get();
    }

    public function communityStats(): array
    {
        $query = ContributionEvent::query()
            ->where('status', ContributionEvent::STATUS_AWARDED);

        return [
            'total_points' => (int) $query->sum('points'),
            'total_events' => (int) (clone $query)->count(),
            'total_contributors' => (int) (clone $query)->distinct('user_id')->count('user_id'),
        ];
    }
}

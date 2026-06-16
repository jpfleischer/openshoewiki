<x-filament-panels::page>
    @php
        /** @var \App\Filament\Resources\CandidateEdits\Pages\ReviewCandidateEdit $this */
        $candidateEdit = $this->getCandidateEdit();
        $diffRows = $this->getDiffRows();
        $decision = $this->getDecisionSnapshot();
    @endphp

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,1.45fr)]">
        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                            {{ $candidateEdit->title ?: 'Candidate edit for '.$candidateEdit->item->english_name }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Proposed by {{ $candidateEdit->proposer->username }} for {{ $candidateEdit->item->english_name }}.
                        </p>
                    </div>
                    <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                        <div>Status: {{ $candidateEdit->statusLabel() }}</div>
                        <div>Risk: {{ $candidateEdit->riskLabel() }}</div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($candidateEdit->status === \App\Models\ItemCandidateEdit::STATUS_OPEN)
                        <x-filament::button size="sm" color="gray" wire:click="processNow">
                            Process Now
                        </x-filament::button>
                    @endif

                    @if (in_array($candidateEdit->status, [\App\Models\ItemCandidateEdit::STATUS_OPEN, \App\Models\ItemCandidateEdit::STATUS_ACCEPTED_PENDING_STAFF], true))
                        <x-filament::button size="sm" color="success" wire:click="applyNow">
                            Apply to Live Item
                        </x-filament::button>

                        <x-filament::button size="sm" color="danger" wire:click="rejectNow">
                            Reject
                        </x-filament::button>
                    @endif

                    <x-filament::button
                        size="sm"
                        color="gray"
                        tag="a"
                        href="{{ route('candidate-edits.show', $candidateEdit) }}"
                        target="_blank"
                    >
                        Open Public Review Page
                    </x-filament::button>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Decision Gate</h3>
                <div class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                    <div class="flex justify-between gap-4"><span>Voting window expired</span><span>{{ $decision['window_expired'] ? 'Yes' : 'No' }}</span></div>
                    <div class="flex justify-between gap-4"><span>Approvals exceed rejections</span><span>{{ $decision['approvals_exceed_rejections'] ? 'Yes' : 'No' }}</span></div>
                    <div class="flex justify-between gap-4"><span>Minimum votes met</span><span>{{ $decision['min_votes_met'] ? 'Yes' : 'No' }}</span></div>
                    <div class="flex justify-between gap-4"><span>Minimum margin met</span><span>{{ $decision['min_margin_met'] ? 'Yes' : 'No' }}</span></div>
                    <div class="flex justify-between gap-4"><span>Risk allows auto-apply</span><span>{{ $decision['risk_allows_auto_apply'] ? 'Yes' : 'No' }}</span></div>
                    <div class="flex justify-between gap-4"><span>Base revision still clean</span><span>{{ $decision['base_revision_clean'] ? 'Yes' : 'No' }}</span></div>
                </div>

                <div class="mt-4 grid gap-2 text-xs text-gray-500 dark:text-gray-400 sm:grid-cols-2">
                    <div>Approvals: {{ $decision['tallies']['approve'] }}</div>
                    <div>Rejections: {{ $decision['tallies']['reject'] }}</div>
                    <div>Total votes required: {{ $decision['threshold']['min_votes'] }}</div>
                    <div>Margin required: {{ $decision['threshold']['min_margin'] }}</div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Proposal Summary</h3>
                <p class="mt-3 whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-200">{{ $candidateEdit->summary ?: 'No summary provided.' }}</p>
                <div class="mt-4 grid gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <div><span class="font-medium">Pair:</span> {{ $candidateEdit->item->english_name }}</div>
                    <div><span class="font-medium">Brand:</span> {{ $candidateEdit->item->brand->name ?? 'Unknown' }}</div>
                    <div><span class="font-medium">Base Revision:</span> #{{ $candidateEdit->baseRevision?->revision_number }}</div>
                    <div><span class="font-medium">Window Ends:</span> {{ $candidateEdit->vote_window_ends_at?->toDayDateTimeString() }}</div>
                    <div><span class="font-medium">Applied Revision:</span> {{ $candidateEdit->appliedRevision?->revision_number ? '#'.$candidateEdit->appliedRevision->revision_number : 'None' }}</div>
                    <div><span class="font-medium">Resolved By:</span> {{ $candidateEdit->resolver?->name ?? $candidateEdit->resolver?->username ?? 'Not resolved yet' }}</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Voter</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Vote</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Cast</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($candidateEdit->votes as $vote)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $vote->user->username }}</td>
                                <td class="px-4 py-3 text-sm {{ $vote->vote === 1 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">{{ $vote->vote === 1 ? 'Approve' : 'Reject' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $vote->created_at?->toDayDateTimeString() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">No votes recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Changed Fields</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Compared against base revision #{{ $candidateEdit->baseRevision?->revision_number }}</span>
                    </div>
                </div>

                @if ($diffRows !== [])
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Field</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Current Value</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Proposed Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                @foreach ($diffRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 align-top text-sm font-medium text-gray-700 dark:text-gray-200">{{ $row['label'] }}</td>
                                        <td class="px-4 py-3 align-top text-sm text-gray-600 dark:text-gray-300">
                                            @if ($row['removed_lines'] !== [] || $row['added_lines'] !== [])
                                                <div class="space-y-1">
                                                    @foreach ($row['removed_lines'] as $line)
                                                        <div class="whitespace-pre-wrap break-words rounded bg-rose-50 px-2 py-1 font-mono text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-200">- {{ $line }}</div>
                                                    @endforeach
                                                    @foreach ($row['added_lines'] as $line)
                                                        <div class="whitespace-pre-wrap break-words rounded bg-emerald-50 px-2 py-1 font-mono text-xs text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">+ {{ $line }}</div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="whitespace-pre-wrap break-words rounded bg-rose-50 px-2 py-1 font-mono text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-200">{{ $row['from'] === null ? 'null' : $row['from'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-top text-sm text-gray-600 dark:text-gray-300">
                                            @if ($row['removed_lines'] === [] && $row['added_lines'] === [])
                                                <div class="whitespace-pre-wrap break-words rounded bg-emerald-50 px-2 py-1 font-mono text-xs text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ $row['to'] === null ? 'null' : $row['to'] }}</div>
                                            @else
                                                <span class="text-xs text-gray-500 dark:text-gray-400">See highlighted changes</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">
                        No public-field diff is available for this candidate edit.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>

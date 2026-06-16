<x-filament-panels::page>
    @php
        /** @var \App\Filament\Resources\Items\Pages\ItemHistory $this */
        $record = $this->getRecord();
        $revisions = $this->getRevisions();
        $selectedRevision = $this->getSelectedRevision();
        $diffRows = $this->getDiffRows();
    @endphp

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,1.4fr)]">
        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ $record->english_name }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ $revisions->count() }} revision{{ $revisions->count() === 1 ? '' : 's' }} saved for this item.
                </p>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Revision</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Event</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Actor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Saved</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($revisions as $revision)
                            <tr @class([
                                'bg-primary-50/70 dark:bg-primary-500/10' => $selectedRevision?->is($revision),
                            ])>
                                <td class="px-4 py-3 align-top">
                                    <button
                                        type="button"
                                        wire:click="selectRevision('{{ $revision->getKey() }}')"
                                        class="text-left text-sm font-medium text-primary-600 hover:underline dark:text-primary-400"
                                    >
                                        #{{ $revision->revision_number }}
                                    </button>
                                    @if ($revision->summary)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $revision->summary }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-gray-700 dark:text-gray-200">
                                    {{ str($revision->event)->replace('_', ' ')->title() }}
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-gray-700 dark:text-gray-200">
                                    {{ $revision->user?->name ?? $revision->user?->username ?? 'System' }}
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-gray-700 dark:text-gray-200">
                                    {{ $revision->created_at?->toDayDateTimeString() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">
                                    No revisions have been saved for this item yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Revision Diff</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Only fields that changed are shown, compared against the current live item state.</p>
                    </div>

                    @if ($this->canRestoreSelectedRevision())
                        <x-filament::button
                            type="button"
                            color="warning"
                            size="sm"
                            wire:click="restoreSelectedRevision"
                            x-on:click="if (! confirm('Restore this item to the selected revision?')) { $event.stopImmediatePropagation(); }"
                        >
                            Restore This Revision
                        </x-filament::button>
                    @endif
                </div>

                @if ($selectedRevision)
                    <div class="mt-3 grid gap-2 text-sm text-gray-700 dark:text-gray-200 sm:grid-cols-2">
                        <div><span class="font-medium">Revision:</span> #{{ $selectedRevision->revision_number }}</div>
                        <div><span class="font-medium">Event:</span> {{ str($selectedRevision->event)->replace('_', ' ')->title() }}</div>
                        <div><span class="font-medium">Actor:</span> {{ $selectedRevision->user?->name ?? $selectedRevision->user?->username ?? 'System' }}</div>
                        <div><span class="font-medium">Saved:</span> {{ $selectedRevision->created_at?->toDayDateTimeString() }}</div>
                    </div>
                @endif
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Changed Fields</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Compared against the previous revision</span>
                    </div>
                </div>

                @if ($diffRows !== [])
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Field</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Revision Value</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Current Value</th>
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
                        The live item already matches this revision.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>

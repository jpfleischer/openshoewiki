@extends('layouts.app', ['title' => "History for {$item->english_name}"])

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8">
                <p class="text-uppercase text-muted small mb-2">Revision History</p>
                <h1 class="h3 mb-2">{{ $item->english_name }}</h1>
                <p class="text-muted mb-0">
                    Public change history for this pair. This page is read-only and does not expose moderation-only fields.
                </p>
            </div>
            <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                <a class="btn btn-outline-primary" href="{{ route('items.show', $item) }}">Back to Pair</a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h5 mb-2">Timeline</h2>
                        <p class="text-muted small mb-3">
                            {{ $revisions->count() }} revision{{ $revisions->count() === 1 ? '' : 's' }}
                        </p>

                        @forelse ($revisions as $revision)
                            <a
                                href="{{ route('items.history', ['item' => $item, 'revision' => $revision->getKey()]) }}"
                                class="d-block rounded border p-3 mb-2 text-decoration-none {{ $selectedRevision && $selectedRevision->is($revision) ? 'border-primary bg-light' : 'border-light bg-white' }}"
                            >
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="font-weight-bold text-dark">Revision #{{ $revision->revision_number }}</div>
                                        <div class="small text-muted">{{ $revisionService->publicEventLabel($revision->event) }}</div>
                                    </div>
                                    <div class="small text-muted text-right">
                                        {{ optional($revision->created_at)->format('M j, Y') }}<br>
                                        {{ optional($revision->created_at)->format('H:i') }} UTC
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="text-muted mb-0">No public history is available for this pair yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Selected Revision</h2>

                        @if ($selectedRevision)
                            <div class="row text-muted small">
                                <div class="col-md-4 mb-2">
                                    <strong>Revision:</strong> #{{ $selectedRevision->revision_number }}
                                </div>
                                <div class="col-md-4 mb-2">
                                    <strong>Event:</strong> {{ $revisionService->publicEventLabel($selectedRevision->event) }}
                                </div>
                                <div class="col-md-4 mb-2">
                                    <strong>Saved:</strong> {{ optional($selectedRevision->created_at)->toDayDateTimeString() }}
                                </div>
                            </div>
                        @else
                            <p class="text-muted mb-0">No revision selected.</p>
                        @endif
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Changed Fields</h2>
                            <span class="small text-muted">
                                Compared against {{ $comparisonRevision ? 'revision #'.$comparisonRevision->revision_number : 'an empty baseline' }}
                            </span>
                        </div>
                        <p class="text-muted small mb-3">Only fields that changed in this revision are shown here.</p>

                        @if ($diffRows !== [])
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>Previous Value</th>
                                            <th>Revision Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($diffRows as $row)
                                            <tr>
                                                <td class="font-weight-bold align-top">{{ $row['label'] }}</td>
                                                <td class="align-top">
                                                    @if ($row['removed_lines'] !== [] || $row['added_lines'] !== [])
                                                        <div class="small">
                                                            @foreach ($row['removed_lines'] as $line)
                                                                <div class="mb-1 rounded px-2 py-1" style="background: #fff1f0; color: #a61b1b; white-space: pre-wrap;">- {{ $line }}</div>
                                                            @endforeach
                                                            @foreach ($row['added_lines'] as $line)
                                                                <div class="mb-1 rounded px-2 py-1" style="background: #f0fff4; color: #1f7a3d; white-space: pre-wrap;">+ {{ $line }}</div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="rounded px-2 py-1" style="background: #fff1f0; color: #a61b1b; white-space: pre-wrap;">- {{ $row['from'] === null ? 'null' : $row['from'] }}</div>
                                                    @endif
                                                </td>
                                                <td class="align-top">
                                                    @if ($row['removed_lines'] === [] && $row['added_lines'] === [])
                                                        <div class="rounded px-2 py-1" style="background: #f0fff4; color: #1f7a3d; white-space: pre-wrap;">+ {{ $row['to'] === null ? 'null' : $row['to'] }}</div>
                                                    @else
                                                        <span class="text-muted small">See highlighted changes</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">This revision does not differ from the comparison snapshot.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

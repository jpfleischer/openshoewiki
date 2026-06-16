@extends('layouts.app', ['title' => 'Candidate Edit'])

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8">
                <p class="text-uppercase text-muted small mb-2">Candidate Edit</p>
                <h1 class="h3 mb-2">{{ $candidateEdit->title ?: 'Candidate edit for '.$candidateEdit->item->english_name }}</h1>
                <p class="text-muted mb-0">
                    Proposed by {{ $candidateEdit->proposer->username }} for
                    <a href="{{ route('items.show', $candidateEdit->item) }}">{{ $candidateEdit->item->english_name }}</a>.
                </p>
            </div>
            <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                <a class="btn btn-outline-primary" href="{{ route('candidate-edits.index') }}">Back to Queue</a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-12">
                @include('items.candidate-edits._criteria')
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Proposal Details</h2>
                        <div class="small text-muted mb-2"><strong>Status:</strong> {{ $candidateEdit->statusLabel() }}</div>
                        <div class="small text-muted mb-2"><strong>Risk:</strong> {{ $candidateEdit->riskLabel() }}</div>
                        <div class="small text-muted mb-2"><strong>Voting Window Ends:</strong> {{ optional($candidateEdit->vote_window_ends_at)->toDayDateTimeString() }}</div>
                        <div class="small text-muted mb-0"><strong>Base Revision:</strong> #{{ optional($candidateEdit->baseRevision)->revision_number }}</div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Summary</h2>
                        <p class="text-muted mb-0" style="white-space: pre-wrap;">{{ $candidateEdit->summary ?: 'No summary provided.' }}</p>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Voting</h2>
                        <div class="d-flex justify-content-between mb-3">
                            <div class="text-success font-weight-bold">+{{ $candidateEdit->approve_votes_count }}</div>
                            <div class="text-danger font-weight-bold">-{{ $candidateEdit->reject_votes_count }}</div>
                        </div>

                        @auth
                            @if ($candidateEdit->user_id === auth()->id())
                                <p class="text-muted small mb-0">You proposed this edit, so you cannot vote on it.</p>
                            @elseif (! $candidateEdit->isOpen())
                                <p class="text-muted small mb-0">Voting is closed for this candidate edit.</p>
                            @else
                                <form method="POST" action="{{ route('candidate-edits.vote', $candidateEdit) }}" class="mb-2">
                                    @csrf
                                    <input type="hidden" name="vote" value="1">
                                    <button type="submit" class="btn btn-outline-success btn-block">
                                        Approve
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('candidate-edits.vote', $candidateEdit) }}">
                                    @csrf
                                    <input type="hidden" name="vote" value="-1">
                                    <button type="submit" class="btn btn-outline-danger btn-block">
                                        Reject
                                    </button>
                                </form>

                                @if ($currentVote)
                                    <p class="text-muted small mt-3 mb-0">
                                        Your current vote:
                                        {{ $currentVote->vote === 1 ? 'Approve' : 'Reject' }}
                                    </p>
                                @endif
                            @endif
                        @else
                            <p class="text-muted small mb-0">
                                <a href="{{ route('login') }}">Sign in</a> to vote on this candidate edit.
                            </p>
                        @endauth

                        @if ($canModerate)
                            <hr class="my-4">
                            <p class="text-muted small mb-2">Staff Actions</p>

                            @if (in_array($candidateEdit->status, [\App\Models\ItemCandidateEdit::STATUS_OPEN, \App\Models\ItemCandidateEdit::STATUS_ACCEPTED_PENDING_STAFF], true))
                                <form method="POST" action="{{ route('candidate-edits.apply', $candidateEdit) }}" class="mb-2">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary btn-block">Apply to Live Item</button>
                                </form>

                                <form method="POST" action="{{ route('candidate-edits.reject', $candidateEdit) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-block">Reject Candidate Edit</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Changed Fields</h2>
                            <span class="small text-muted">
                                Compared against revision #{{ optional($candidateEdit->baseRevision)->revision_number }}
                            </span>
                        </div>

                        @if ($diffRows !== [])
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>Current Value</th>
                                            <th>Proposed Value</th>
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
                            <p class="text-muted mb-0">This candidate edit does not change any public fields.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

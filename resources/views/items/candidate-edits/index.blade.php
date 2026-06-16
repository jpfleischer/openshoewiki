@extends('layouts.app', ['title' => 'Candidate Edits'])

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8">
                <p class="text-uppercase text-muted small mb-2">Community Review</p>
                <h1 class="h3 mb-2">Candidate Edits</h1>
                <p class="text-muted mb-0">
                    Review proposed changes before they become part of the archive.
                </p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-12">
                @include('items.candidate-edits._criteria')
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        @if ($candidateEdits->count() === 0)
                            <p class="text-muted mb-0">No candidate edits have been submitted yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Pair</th>
                                            <th>Proposal</th>
                                            <th>Risk</th>
                                            <th>Status</th>
                                            <th>Votes</th>
                                            <th>Window Ends</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($candidateEdits as $candidateEdit)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('items.show', $candidateEdit->item) }}">{{ $candidateEdit->item->english_name }}</a>
                                                    <div class="small text-muted">{{ $candidateEdit->item->brand->name ?? 'Unknown brand' }}</div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('candidate-edits.show', $candidateEdit) }}">
                                                        {{ $candidateEdit->title ?: 'Candidate edit for '.$candidateEdit->item->english_name }}
                                                    </a>
                                                    <div class="small text-muted">by {{ $candidateEdit->proposer->username }}</div>
                                                </td>
                                                <td>{{ $candidateEdit->riskLabel() }}</td>
                                                <td>{{ $candidateEdit->statusLabel() }}</td>
                                                <td>
                                                    <span class="text-success">+{{ $candidateEdit->approve_votes_count }}</span>
                                                    /
                                                    <span class="text-danger">-{{ $candidateEdit->reject_votes_count }}</span>
                                                </td>
                                                <td>{{ optional($candidateEdit->vote_window_ends_at)->format('M j, Y H:i') }} UTC</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                {{ $candidateEdits->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

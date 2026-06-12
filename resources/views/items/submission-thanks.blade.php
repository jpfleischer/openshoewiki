@extends('layouts.app', ['title' => 'Submission Received'])

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card shadow-sm">
                <div class="card-body p-5 text-center">
                    <h1 class="h2 mb-3">Submission Received</h1>
                    <p class="lead mb-2">{{ $item->english_name }}</p>
                    <p class="text-muted mb-4">
                        @if ($item->published())
                            Your pair has been published to the archive.
                        @else
                            Your pair has been saved in the archive and is waiting for review.
                        @endif
                    </p>

                    <div class="mb-4">
                        @include('items.status-badge', ['item' => $item])
                    </div>

                    <div class="alert alert-light text-left">
                        <p class="mb-2"><strong>What happens next</strong></p>
                        <p class="mb-0">
                            @if ($item->published())
                                This record is now live and visible in the public archive.
                            @else
                                Staff can review it, publish it, or request changes. Until then, only you and staff can open this record.
                            @endif
                        </p>
                    </div>

                    <div class="d-flex justify-content-center flex-wrap">
                        <a href="{{ route('items.show', $item) }}" class="btn btn-primary m-2">View Submission</a>
                        <a href="{{ route('submit.index') }}" class="btn btn-outline-primary m-2">My Submissions</a>
                        <a href="{{ route('submit.create') }}" class="btn btn-outline-secondary m-2">Submit Another Pair</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

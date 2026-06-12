@extends('layouts.app', ['title' => 'My Submissions'])

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-lg-8">
            <h1 class="h2 mb-2">My Submissions</h1>
            <p class="text-muted mb-0">Track the status of the pairs you have submitted to the archive.</p>
        </div>
        <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
            <a href="{{ route('submit.create') }}" class="btn btn-primary">Submit Another Pair</a>
        </div>
    </div>

    @if ($items->count() > 0)
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Pair</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>
                                    <div class="font-weight-bold">{{ $item->english_name }}</div>
                                    <div class="small text-muted">
                                        {{ $item->brand->name }}
                                        @if ($item->categories->isNotEmpty())
                                            · {{ $item->categories->pluck('name')->join(', ') }}
                                        @endif
                                    </div>
                                </td>
                                <td>@include('items.status-badge', ['item' => $item])</td>
                                <td>{{ optional($item->created_at)->format('M j, Y') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('items.show', $item) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    @if (! $item->published())
                                        <a href="{{ route('submit.thanks', $item) }}" class="btn btn-sm btn-outline-secondary">Status</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $items->links() }}
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <h2 class="h4 mb-3">No submissions yet</h2>
                <p class="text-muted mb-4">Start by creating your first shoe record.</p>
                <a href="{{ route('submit.create') }}" class="btn btn-primary">Submit a Pair</a>
            </div>
        </div>
    @endif
</div>
@endsection

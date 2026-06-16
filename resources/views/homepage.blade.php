@extends('layouts.app')

@section('content')
<div class="container">
    @include('components.hero')
    @php($fallbackThumbnail = cdn_thumbnail('categories/other.svg'))

    {{-- todo: put brands in here with their images --}}
    {{-- todo: carousel these! (or scroll left/right) --}}
    <h2 class="mt-5">{{ __('ui.brands') }}</h2>
    <div class="scrollbox">
        @foreach ($brands as $brand)
        <div class="scrollbox-item m-2">
            <div class="card shadow-sm scrollbox-square">
                <a href="{{ $brand->url }}">
                    <div class="scrollbox-img">
                        <img src="{{ $brand->image ? cdn_thumbnail($brand->image) : $fallbackThumbnail }}" alt=""
                            data-original-url="{{ $brand->image ? cdn_thumbnail($brand->image) : $fallbackThumbnail }}"
                            onerror="if (this.src !== '{{ $fallbackThumbnail }}') this.src = '{{ $fallbackThumbnail }}'">
                    </div>
                    <div class="scrollbox-text">
                        <p class="text-muted small p-0 m-0">{{ $brand->name }}</p>
                    </div>
                </a>
            </div>
        </div>
        @endforeach
    </div>

    <h2 class="mt-5">{{ __('ui.categories') }}</h2>
    <div class="scrollbox">
        @foreach ($categories as $category)
        <div class="scrollbox-item m-2">
            <div class="card shadow-sm scrollbox-square">
                <a href="{{ $category->url }}">
                    <div class="scrollbox-img">
                        <img src="{{ $category->image ? cdn_thumbnail($category->image) : $fallbackThumbnail }}" alt=""
                            data-original-url="{{ $category->image ? cdn_thumbnail($category->image) : $fallbackThumbnail }}"
                            onerror="if (this.src !== '{{ $fallbackThumbnail }}') this.src = '{{ $fallbackThumbnail }}'">
                    </div>
                    <div class="scrollbox-text">
                        <p class="text-muted small p-0 m-0">{{ $category->name }}</p>
                    </div>
                </a>
            </div>
        </div>
        @endforeach
    </div>

    <h2 class="mt-5">Community Leaderboard</h2>
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-uppercase text-muted small mb-2">Archive Activity</p>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span>Total points</span>
                        <strong>{{ number_format($communityStats['total_points']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span>Scored edits</span>
                        <strong>{{ number_format($communityStats['total_events']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span>Contributors</span>
                        <strong>{{ number_format($communityStats['total_contributors']) }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="text-uppercase text-muted small mb-1">Top Contributors</p>
                            <h3 class="h5 mb-0">Who is building OpenShoeWiki</h3>
                        </div>
                    </div>

                    @if ($leaderboard->isEmpty())
                        <p class="text-muted mb-0">No contribution points have been recorded yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Rank</th>
                                        <th scope="col">Contributor</th>
                                        <th scope="col" class="text-right">Points</th>
                                        <th scope="col" class="text-right">Events</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($leaderboard as $index => $contributor)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $contributor->username }}</td>
                                            <td class="text-right">{{ number_format($contributor->contributionPoints()) }}</td>
                                            <td class="text-right">{{ number_format($contributor->contributionCount()) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <h2 class="mt-5">{{ __('ui.recent_items') }}</h2>
    <div class="scrollbox">
        @foreach ($recent as $item)
            <div class="scrollbox-item scrollbox-item-card m-2">
                @include('items.card', compact('item'))
            </div>
        @endforeach
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="text-center m-4">
                <img src="{{ cdn_link('categories/other.svg') }}" alt="" style="max-height: 150px; max-width: 150px" class="img-thumbnail circle">
            </div>
            <div class="text-center m-4">
                {{ $user->username }}
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <span class="text-muted">Contribution Points</span>
                        <strong>{{ number_format($user->contributionPoints()) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Scored Events</span>
                        <strong>{{ number_format($user->contributionCount()) }}</strong>
                    </div>
                </div>
            </div>

            <div class="list-group">
                    @if ($isOwner)
                        <a href="{{ route('profile') }}" class="list-group-item list-group-item-action @if (Route::is('profile')) active @endif">
                            <i data-feather="user" class="icon-fw"></i>
                            {{ __('ui.profile') }}
                        </a>
                    @endif
                    @if ($isOwner || $user->public_wishlist)
                    <a href="{{ route('public_wishlist', ['username' => $user->username]) }}" class="list-group-item list-group-item-action @if (Route::is('public_wishlist')) active @endif">
                        <i data-feather="star" class="icon-fw"></i>
                        {{ __('ui.wishlist.title') }}
                    </a>
                    @endif
                    @if ($isOwner || $user->public_closet)
                    <a href="{{ route('public_closet', ['username' => $user->username]) }}" class="list-group-item list-group-item-action @if (Route::is('public_closet')) active @endif">
                        <i data-feather="tag" class="icon-fw"></i>
                        {{ __('ui.closet.title') }}
                    </a>
                    @endif
            </div>
        </div>
        <div class="col-md-8">
            @yield('profile', '')
        </div>
    </div>
</div>
@endsection

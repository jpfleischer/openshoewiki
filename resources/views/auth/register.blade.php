@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('ui.auth.register') }}</div>

                <div class="card-body">
                    <div class="text-center mb-4">
                        <p class="text-muted mb-3">OpenShoeWiki creates new accounts through Discord and does not retain a Discord email address.</p>
                        <a class="btn btn-primary btn-lg btn-block" href="{{ route('auth.discord.redirect') }}">
                            Create Account with Discord
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

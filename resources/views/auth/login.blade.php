@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('ui.login') }}</div>

                <div class="card-body">
                    <div class="text-center mb-4">
                        <p class="text-muted mb-3">Sign in with your Discord account to access OpenShoeWiki. We use your Discord identity and do not retain a Discord email address.</p>
                        <a class="btn btn-primary btn-lg btn-block" href="{{ route('auth.discord.redirect') }}">
                            Continue with Discord
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

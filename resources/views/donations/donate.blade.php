@extends('layouts.app')

@section('content')
<div class="container">
    @include('components.hero')

    <div class="row p-0">
        <div class="col-sm-10 col-md-8 col-lg-6 offset-sm-1 offset-md-2 offset-lg-3 px-2 text-center">
          <p>{{ __('ui.donate.txt1')}}</p>
          <p>@lang('ui.donate.txt2')</p>

          <p>{{ __('ui.donate.other')}}</p>

          <p class="my-4">
            <a href="#" class="mx-3 btn btn-lg btn-outline-primary">
              {{ __('ui.donate.title')}}
            </a>
          </p>

          <p class="small text-muted">{{ __('ui.donate.npo')}}</p>

        </div>
    </div>

</div>
@endsection

@section('meta')
    <link rel="canonical" href="{{ route('donate') }}">

    <meta property="og:url" content="{{ route('donate') }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="Support OpenShoeWiki">
    <meta property="og:image" content="{{ cdn_link('assets/banners/banner01-white.png') }}">
@endsection

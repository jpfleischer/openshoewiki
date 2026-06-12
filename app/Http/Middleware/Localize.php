<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class Localize
{
    /**
     * Set locale for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    public function handle($request, Closure $next)
    {
        App::setLocale('en');

        $lang = $request->session()->get('lang');

        if ($lang === 'en') {
            App::setLocale('en');
        }

        return $next($request);
    }
}

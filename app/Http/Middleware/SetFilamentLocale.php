<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetFilamentLocale
{
    /**
     * Force the admin panel to render in Simplified Chinese
     * regardless of the application's default locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale('zh_CN');

        return $next($request);
    }
}

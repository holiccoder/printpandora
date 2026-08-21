<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateChatApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('aichat.api_token');
        $provided = (string) $request->bearerToken();

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'message' => 'Invalid or missing API token.',
            ], 401, [
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $username = env('BASIC_AUTH_USERNAME');
        $password = env('BASIC_AUTH_PASSWORD');

        if (!$username || !$password) {
            // If credentials are not set in .env, deny access
            return response('Unauthorized.', 401, ['WWW-Authenticate' => 'Basic']);
        }

        $authHeader = $request->header('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Basic ')) {
            return response('Unauthorized.', 401, ['WWW-Authenticate' => 'Basic']);
        }

        $credentials = base64_decode(substr($authHeader, 6));
        list($requestUsername, $requestPassword) = explode(':', $credentials, 2);

        if ($requestUsername === $username && $requestPassword === $password) {
            return $next($request);
        }

        return response('Unauthorized.', 401, ['WWW-Authenticate' => 'Basic']);
    }
}

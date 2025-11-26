<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Users;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->get('api_key');

        if (!$apiKey) {
            return response()->json(['message' => 'API key is required'], 401);
        }

        $user = Users::where('api_key', $apiKey)->where('is_active', true)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid API key'], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}

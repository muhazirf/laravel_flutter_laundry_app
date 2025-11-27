<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ApiResponseService;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTTenantMiddleware
{
    /**
     * Handle an incoming request and extract tenant context from JWT
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Get JWT token from request
            $token = JWTAuth::getToken();

            if (!$token) {
                return ApiResponseService::unauthorized('JWT token required');
            }

            // Get JWT payload
            $payload = JWTAuth::getPayload();

            // Validate required claims
            if (!$payload->has('tenant') || !$payload->has('permissions')) {
                return ApiResponseService::error('Invalid JWT: Missing tenant claims', null, null, 401);
            }

            // Extract tenant context from JWT claims
            $tenantContext = [
                'current_outlet_id' => $payload->get('tenant.current_outlet_id'),
                'available_outlets' => $payload->get('tenant.available_outlets', []),
                'primary_role' => $payload->get('tenant.primary_role'),
                'user_id' => $payload->get('user.id'),
                'user_email' => $payload->get('user.email'),
                'session_context' => $payload->get('tenant.session_context', []),
            ];

            // Extract permissions for all outlets
            $permissions = $payload->get('permissions', []);

            // Add tenant context to request for controllers
            $request->merge([
                'tenant_context' => $tenantContext,
                'jwt_permissions' => $permissions,
            ]);

            // Share tenant context with views if needed
            view()->share('tenant_context', $tenantContext);

            return $next($request);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return ApiResponseService::unauthorized('Token has expired');
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return ApiResponseService::unauthorized('Invalid token');
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return ApiResponseService::unauthorized('Token error: ' . $e->getMessage());
        } catch (\Exception $e) {
            return ApiResponseService::serverError('Authentication error', ['error' => $e->getMessage()]);
        }
    }
}
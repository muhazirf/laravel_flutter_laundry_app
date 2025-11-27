<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ApiResponseService;
use App\Services\JWTClaimsService;
use Tymon\JWTAuth\Facades\JWTAuth;

class HasPermissionMiddleware
{
    /**
     * Handle an incoming request and check permissions
     *
     * @param Request $request
     * @param \Closure $next
     * @param string $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        try {
            // Get JWT token from request
            $token = JWTAuth::getToken();

            if (!$token) {
                return ApiResponseService::unauthorized('JWT token required');
            }

            // Get JWT payload
            $payload = JWTAuth::getPayload();
            $claims = $payload->toArray();

            // Get outlet ID from route parameter or request
            $outletId = $request->route('outlet_id') ??
                       $request->route('id') ??
                       $request->get('outlet_id') ??
                       $request->get('id');

            // If no outlet ID provided, use current outlet from JWT
            if (!$outletId) {
                $outletId = $claims['tenant']['current_outlet_id'] ?? null;
            }

            if (!$outletId) {
                return ApiResponseService::error('Outlet ID required', null, null, 400);
            }

            // Check if user can access the outlet
            if (!JWTClaimsService::canAccessOutlet($claims, (int) $outletId)) {
                return ApiResponseService::forbidden('Access denied: You do not have access to this outlet');
            }

            // Check if user has the required permission for this outlet
            if (!JWTClaimsService::hasPermission($claims, (int) $outletId, $permission)) {
                return ApiResponseService::forbidden('Access denied: Insufficient permissions');
            }

            // Add outlet context to request
            $request->merge([
                'current_outlet_id' => $outletId,
                'user_permissions_for_outlet' => JWTClaimsService::getEffectivePermissions($claims, (int) $outletId),
            ]);

            return $next($request);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return ApiResponseService::unauthorized('Token has expired');
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return ApiResponseService::unauthorized('Invalid token');
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return ApiResponseService::unauthorized('Token error: ' . $e->getMessage());
        } catch (\Exception $e) {
            return ApiResponseService::serverError('Permission check error', ['error' => $e->getMessage()]);
        }
    }
}
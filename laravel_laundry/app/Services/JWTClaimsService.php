<?php

namespace App\Services;

use App\Models\User;
use App\Domain\OutletPermissions;
use Illuminate\Support\Str;

class JWTClaimsService
{
    /**
     * Generate enhanced JWT claims with tenant context and permissions
     *
     * @param User $user
     * @return array
     */
    public static function generateEnhancedClaims(User $user): array
    {
        $userOutlets = $user->userOutletsActive()
            ->with('outlet:id,name,address,phone')
            ->get();

        $permissions = [];
        $availableOutlets = [];
        $outletDetails = [];

        foreach ($userOutlets as $userOutlet) {
            $outletId = $userOutlet->outlet_id;
            $availableOutlets[] = $outletId;

            // Store outlet details for frontend
            $outletDetails["outlet_{$outletId}"] = [
                'id' => $outletId,
                'name' => $userOutlet->outlet->name,
                'address' => $userOutlet->outlet->address,
                'phone' => $userOutlet->outlet->phone,
            ];

            // Get base permissions for role using existing OutletPermissions class
            $basePermissions = OutletPermissions::defaultsFor($userOutlet->role) ?? [];

            // Get custom permissions from JSON (already cast to array by model)
            $customPermissions = $userOutlet->permissions_json ?? [];
            $customPermissions = is_array($customPermissions) ? $customPermissions : [];

            $permissions["outlet_{$outletId}"] = [
                'role' => $userOutlet->role,
                'permissions' => $basePermissions,
                'overrides' => $customPermissions,
                'is_active' => (bool) $userOutlet->is_active,
                'joined_at' => $userOutlet->created_at->toISOString(),
            ];
        }

        // Get primary outlet (first active one)
        $primaryOutlet = $userOutlets->first();
        $currentOutletId = $primaryOutlet ? $primaryOutlet->outlet_id : null;
        $primaryRole = $primaryOutlet ? $primaryOutlet->role : null;

        return [
            'sub' => "user_{$user->id}",
            'iat' => now()->timestamp,
            'jti' => Str::uuid()->toString(),
            'type' => 'access',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => (bool) $user->is_active,
            ],
            'tenant' => [
                'current_outlet_id' => $currentOutletId,
                'available_outlets' => $availableOutlets,
                'primary_role' => $primaryRole,
                'outlet_details' => $outletDetails,
                'session_context' => [
                    'device_id' => request()->header('X-Device-ID', 'unknown'),
                    'last_activity' => now()->timestamp,
                ]
            ],
            'permissions' => $permissions,
        ];
    }

    
    /**
     * Validate if user has permission for specific outlet
     *
     * @param array $claims
     * @param int $outletId
     * @param string $permission
     * @return bool
     */
    public static function hasPermission(array $claims, int $outletId, string $permission): bool
    {
        $outletPermissions = $claims['permissions']["outlet_{$outletId}"] ?? null;

        if (!$outletPermissions || !$outletPermissions['is_active']) {
            return false;
        }

        $userPermissions = $outletPermissions['permissions'];
        $overrides = $outletPermissions['overrides'] ?? [];

        return in_array($permission, $userPermissions) || ($overrides[$permission] ?? false);
    }

    /**
     * Get user's effective permissions for an outlet
     *
     * @param array $claims
     * @param int $outletId
     * @return array
     */
    public static function getEffectivePermissions(array $claims, int $outletId): array
    {
        $outletPermissions = $claims['permissions']["outlet_{$outletId}"] ?? null;

        if (!$outletPermissions) {
            return [];
        }

        $basePermissions = $outletPermissions['permissions'];
        $overrides = $outletPermissions['overrides'] ?? [];

        // Merge permissions (overrides can add or remove)
        $effective = $basePermissions;

        foreach ($overrides as $permission => $granted) {
            if ($granted) {
                if (!in_array($permission, $effective)) {
                    $effective[] = $permission;
                }
            } else {
                $effective = array_filter($effective, fn($p) => $p !== $permission);
            }
        }

        return array_values($effective);
    }

    /**
     * Check if user can access outlet
     *
     * @param array $claims
     * @param int $outletId
     * @return bool
     */
    public static function canAccessOutlet(array $claims, int $outletId): bool
    {
        return in_array($outletId, $claims['tenant']['available_outlets'] ?? []);
    }

    /**
     * Get all outlets user can access
     *
     * @param array $claims
     * @return array
     */
    public static function getAccessibleOutlets(array $claims): array
    {
        $outlets = [];

        foreach ($claims['tenant']['available_outlets'] as $outletId) {
            $outletKey = "outlet_{$outletId}";
            if (isset($claims['tenant']['outlet_details'][$outletKey])) {
                $outlets[] = array_merge(
                    $claims['tenant']['outlet_details'][$outletKey],
                    [
                        'role' => $claims['permissions'][$outletKey]['role'] ?? null,
                        'is_active' => $claims['permissions'][$outletKey]['is_active'] ?? false
                    ]
                );
            }
        }

        return $outlets;
    }
}
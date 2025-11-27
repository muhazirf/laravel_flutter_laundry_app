<?php

namespace App\Services\Api\V1;

use App\Models\User;
use App\Models\Outlet;
use App\Models\UserOutlet;
use Exception;
use App\Domain\OutletPermissions;
use Illuminate\Support\Str;

class Outletervice
{
    public function createForOwner(User $owner, array $data): Outlet
    {
        try {
            if (!$owner->is_active) {
                throw new Exception('sorry you account is in active, please update your subscription');
            }

            $outletData['owner_user_id'] = $owner->id;
            $outletData['is_active']     = $data['is_active'] ?? true;

            $outlet = Outlet::create($outletData);

            $this->assignUser($outlet, $owner, UserOutlet::ROLE_OWNER);

            return $outlet->load(['owner', 'userOutlet']);
        } catch (\Throwable $th) {
            throw new Exception('Failed to create Outlet');
        }
    }

    public function assignUser(Outlet $outlet, User $user, string $role, ?array $permOverride = null): UserOutlet
    {
        if (! $outlet->is_active) {
            throw new Exception('Cannot assign user to inactive outlet');
        }

        if (! $user->is_active) {
            throw new Exception('Cannot assign inactive user to outlet');
        }

        if (! in_array($role, [UserOutlet::ROLE_OWNER, UserOutlet::ROLE_KARYAWAN], true)) {
            throw new Exception('Invalid role specified');
        }

        // Get base permissions for role
        $basePermissions = OutletPermissions::defaultsFor($role);

        // Merge with override permissions
        $finalPermissions = OutletPermissions::merge($basePermissions, $permOverride ?? []);

        // Upsert user outlet relationship
        $userOutlet = UserOutlet::updateOrCreate(
            [
                'user_id' => $user->id,
                'outlet_id' => $outlet->id,
            ],
            [
                'role' => $role,
                'permissions_json' => $finalPermissions,
                'is_active' => true,
            ]
        );

        if (! $userOutlet) {
            throw new Exception('Failed to assign user to outlet');
        }

        return $userOutlet->load(['user', 'outlet']);
    }
}

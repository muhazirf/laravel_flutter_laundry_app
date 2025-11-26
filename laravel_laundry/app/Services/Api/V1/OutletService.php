<?php

namespace App\Services\Api\V1;

use App\Models\Users;
use App\Models\Outlets;
use App\Models\UserOutlets;
use Exception;
use App\Domain\OutletPermissions;
use Illuminate\Support\Str;

class OutletService
{
    public function createForOwner(Users $owner, array $data): Outlets
    {
        try {
            if (!$owner->is_active) {
                throw new Exception('sorry you account is in active, please update your subscription');
            }

            $outletData['owner_user_id'] = $owner->id;
            $outletData['is_active']     = $data['is_active'] ?? true;

            $outlet = Outlets::create($outletData);

            $this->assignUser($outlet, $owner, UserOutlets::ROLE_OWNER);

            return $outlet->load(['owner', 'userOutlets']);
        } catch (\Throwable $th) {
            throw new Exception('Failed to create Outlet');
        }
    }

    public function assignUser(Outlets $outlet, Users $user, string $role, ?array $permOverride = null): UserOutlets
    {
        if (! $outlet->is_active) {
            throw new Exception('Cannot assign user to inactive outlet');
        }

        if (! $user->is_active) {
            throw new Exception('Cannot assign inactive user to outlet');
        }

        if (! in_array($role, [UserOutlets::ROLE_OWNER, UserOutlets::ROLE_KARYAWAN], true)) {
            throw new Exception('Invalid role specified');
        }

        // Get base permissions for role
        $basePermissions = OutletPermissions::defaultsFor($role);

        // Merge with override permissions
        $finalPermissions = OutletPermissions::merge($basePermissions, $permOverride ?? []);

        // Upsert user outlet relationship
        $userOutlet = UserOutlets::updateOrCreate(
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

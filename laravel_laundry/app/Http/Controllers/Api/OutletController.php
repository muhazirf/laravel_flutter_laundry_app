<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\UserOutlets;
use Illuminate\Http\JsonResponse;
use App\Services\Api\V1\OutletService;
use App\Services\Api\V1\UserService;
use Illuminate\Support\Facades\Auth;
use App\Http\Resource\OutletResource;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class OutletController extends BaseApiController
{
    public function __construct(
        private OutletService $outletService,
        private UserService $userService
    ) {}

    public function getOutlets(): JsonResponse
    {
        $user = Auth::user();

        $userOutlets = UserOutlets::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('outlets')
            ->get();

        $outlets = $userOutlets->map(function ($userOutlet) {
            $outlet = $userOutlet->outlet;
            $outlet->user_role = $userOutlet->role;
            $outlet->user_permissions = $userOutlet->permission_json;

            return $outlet;
        });

        return $this->success(
            OutletResource::collection($outlets),
            'Daftar Outlet berhasil diambil'
        );
    }

    public function checkStatus(): JsonResponse
    {
        $user = Auth::user();

        $outletCount = UserOutlets::where('user_id', $user->id)
            ->where('is_active', true)
            ->with(['outlet:id,name,address,phone,is_active'])
            ->get();

        $isOutlet = $outletCount != 0 ? true : false;

        return $this->success([
                'has_outlets' => $isOutlet,
                'outlet_count' => $outletCount,
                'show_manage_button' => !$isOutlet,
                'user_id' => $user->id,
            ], 'Berhasil mendapatkan status outlet');
    }

    public function getForFlutter(): JsonResponse
    {
        $user = Auth::user();

        $userOutlets = UserOutlets::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('outlet:id,name,address,phone,is_active')
            ->get();

        $outlets = $userOutlets->map(function ($q) {
            $outlet = $q->outlet;
            return [
                'id' => $outlet->id,
                'name' => $outlet->name,
                'address' => $outlet->name,
                'phone' => $outlet->phone,
                'is_active' => $outlet->is_active,
                'user_role' => $q->role,
                'can_manage' => $q->role === UserOutlets::ROLE_OWNER,
                'joined_at' => $q->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return $this->success([
                'outlets' => $outlets,
                'total_count' => $outlets->count(),
                'has_outlets' => $outlets->count() > 0,
                'show_manage_button' => $outlets->count() === 0,
            ], 'Daftar outlet berhasil diambil');
    }
}

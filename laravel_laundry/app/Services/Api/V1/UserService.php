<?php

namespace App\Services\Api\V1;

use App\Models\Users;
use Exception;
use Illuminate\Support\Str;

class UserService
{
    public function findOrCreateByEmailOrPhone(?string $email, ?string $phone, ?string $name = null, ?string $password = null, ?string $address = null): Users
    {
        try {
            if (!$email || !$phone) {
                throw new Exception("Either email or phone must be provided");
            }

            $user = Users::query()
                ->where(function ($query) use ($email, $phone) {
                    if ($email) {
                        $query->where('email', $email);
                    }

                    if ($phone) {
                        $query->where('phone', $phone);
                    }
                })->first();

            if ($user) {
                return $user;
            }

            $userData = [
                'name'      => $name ?? 'User ' . Str::random(6),
                'email'     => $email,
                'phone'     => $phone,
                'password'  => bcrypt($password),
                'address'   => $address,
                'is_active' => true,
            ];

            $user = Users::create($userData);
            return $user;
        } catch (\Throwable $th) {
            throw new Exception("Error, " . $th->getMessage());
        }
    }
}

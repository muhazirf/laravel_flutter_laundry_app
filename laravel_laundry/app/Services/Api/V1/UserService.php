<?php

namespace App\Services\Api\V1;

use App\Models\User;
use Exception;
use Illuminate\Support\Str;

class UserService
{
    public function findOrCreateByEmailOrPhone(?string $email, ?string $phone, ?string $name = null, ?string $password = null, ?string $address = null): User
    {
        try {
            if (!$email || !$phone) {
                throw new Exception("Either email or phone must be provided");
            }

            $user = User::query()
                ->where(function ($query) use ($email, $phone) {
                    if ($email) {
                        $query->orWhere('email', $email);
                    }

                    if ($phone) {
                        $query->orWhere('phone', $phone);
                    }
                })->first();

            if ($user) {
                return $user;
            }

            $userData = [
                'name'      => $name ?? 'User ' . Str::random(6),
                'email'     => $email,
                'phone'     => $phone,
                'password'  => $password ? bcrypt($password) : null,
                'address'   => $address,
                'is_active' => true,
            ];

            $user = User::create($userData);
            return $user;
        } catch (\Throwable $th) {
            throw new Exception("Error, " . $th->getMessage());
        }
    }
}

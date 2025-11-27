<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\JWTClaimsService;
use App\Services\RefreshTokenService;

class AuthController extends BaseApiController
{

    public function register(Request $request): JsonResponse
    {
        // Manual JSON parsing if needed
        if ($request->header('Content-Type') === 'application/json' && empty($request->all())) {
            $jsonInput = json_decode($request->getContent(), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonInput)) {
                $request->merge($jsonInput);
            }
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];

        //validation custom message
        $messages = [
            'name.required' => 'Nama harus diisi',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Password tidak sesuai',
        ];

        $request->validate($rules, $messages);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Generate enhanced JWT with tenant context
            $customClaims = JWTClaimsService::generateEnhancedClaims($user);
            $enhancedToken = JWTAuth::customClaims($customClaims)->fromUser($user);

            // Generate refresh token
            $refreshToken = RefreshTokenService::generateRefreshToken($user);

            return $this->created([
                'user' => $user->makeHidden(['password', 'remember_token']),
                'token' => $enhancedToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // seconds
                'tenant' => [
                    'current_outlet_id' => $customClaims['tenant']['current_outlet_id'],
                    'available_outlets' => $customClaims['tenant']['available_outlets'],
                    'primary_role' => $customClaims['tenant']['primary_role'],
                ]
            ], 'User successfully registered');
        } catch (\Exception $e) {
            return $this->serverError('User registration failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * User authentication endpoint that returns JWT token and API key.
     *
     * @bodyParam email string required The user's email address. Example: test@example.com
     * @bodyParam password string required The user's password. Example: password
     * @response 200 scenario="success" {"message":"User successfully logged in","data":{"user":{"id":9,"name":"Test User","email":"testjwt@example.com"},"token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...","authorization":{"type":"bearer"},"api_key":"lk_d245bca1d1f77f96c34a971759c9f7be"}}
     * @response 401 scenario="invalid_credentials" {"message":"Invalid credentials"}
     * @response 403 scenario="inactive_user" {"message":"User is not active"}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Manual JSON parsing if needed
        if ($request->header('Content-Type') === 'application/json' && empty($request->all())) {
            $jsonInput = json_decode($request->getContent(), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonInput)) {
                $request->merge($jsonInput);
            }
        }


        $credentials = $request->only('email', 'password');

        // 🔧 FIX: Authenticate dengan JWT attempt
        if (!$token = JWTAuth::attempt($credentials)) {
            return $this->unauthorized('Invalid credentials');
        }

        // 🔧 FIXED: Ambil user setelah successful authentication
        $user = JWTAuth::user();

        if (!$user->is_active) {
            return $this->forbidden('User is not active');
        }

        // Generate enhanced JWT with tenant context
        $customClaims = JWTClaimsService::generateEnhancedClaims($user);
        $enhancedToken = JWTAuth::customClaims($customClaims)->fromUser($user);

        // Generate refresh token (optional, fallback if Redis not available)
        try {
            $refreshToken = RefreshTokenService::generateRefreshToken($user);
        } catch (\Exception $e) {
            $refreshToken = null;
            // Log error but continue with access token only
            \Log::warning('Refresh token generation failed: ' . $e->getMessage());
        }

        return $this->success([
            'user' => $user->makeHidden(['password', 'remember_token']),
            'token' => $enhancedToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // seconds
            'tenant' => [
                'current_outlet_id' => $customClaims['tenant']['current_outlet_id'],
                'available_outlets' => $customClaims['tenant']['available_outlets'],
                'primary_role' => $customClaims['tenant']['primary_role'],
            ]
        ], 'User successfully logged in');
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $removeToken = JWTAuth::invalidate(JWTAuth::getToken());
            return $this->success(null, 'Logout successful');
        } catch (\Exception $e) {
            return $this->error('Logout failed', ['error' => $e->getMessage()], null, 400);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return $this->success([
                'authorization' => [
                    'token' => $newToken,
                    'type' => 'bearer',
                ],
            ], 'Token refreshed');
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->unauthorized('Invalid token');
        }
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($request->user()->makeHidden(['password', 'remember_token']), 'User profile retrieved successfully');
    }

    public function sendEmailVerification(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return $this->error('Email already verified', null, null, 400);
        }

        $user->sendEmailVerificationNotification();

        return $this->success(null, 'Verification email sent');
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return $this->error('Email already verified', null, null, 400);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->success(null, 'Email verified successfully');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(null, 'Password reset link sent');
        } else {
            return $this->serverError('Unable to send reset link');
        }
    }

    public function resetPassword(Request $request)
    {
        $rules = [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ];

        $messages = [
            'token.required' => 'Token harus diisi',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Email tidak valid',
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Password tidak sesuai',
            'password_confirmation.required' => 'Konfirmasi password harus diisi',
        ];

        $request->validate($rules, $messages);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );
        return $status === Password::PasswordReset
            ? $this->success(null, 'Password reset successful')
            : $this->error('Password reset failed', null, null, 400);
    }

    /**
     * Refresh JWT token using refresh token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => 'required|string'
        ], [
            'refresh_token.required' => 'Refresh token is required'
        ]);

        $refreshToken = $request->get('refresh_token');

        // Validate refresh token
        $tokenData = RefreshTokenService::validateRefreshToken($refreshToken);

        if (!$tokenData) {
            return $this->unauthorized('Invalid or expired refresh token');
        }

        try {
            // Get user from token data
            $user = User::find($tokenData['user_id']);

            if (!$user || !$user->is_active) {
                RefreshTokenService::revokeRefreshToken($refreshToken);
                return $this->unauthorized('User not found or inactive');
            }

            // Generate new JWT with enhanced claims
            $customClaims = JWTClaimsService::generateEnhancedClaims($user);
            $newToken = JWTAuth::customClaims($customClaims)->fromUser($user);

            // Increment refresh token usage
            RefreshTokenService::incrementUsage($refreshToken);

            return $this->success([
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // seconds
                'tenant' => [
                    'current_outlet_id' => $customClaims['tenant']['current_outlet_id'],
                    'available_outlets' => $customClaims['tenant']['available_outlets'],
                    'primary_role' => $customClaims['tenant']['primary_role'],
                ]
            ], 'Token refreshed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Token refresh failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Revoke refresh token
     */
    public function revokeToken(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => 'required|string'
        ]);

        $refreshToken = $request->get('refresh_token');

        if (RefreshTokenService::revokeRefreshToken($refreshToken)) {
            return $this->success(null, 'Refresh token revoked successfully');
        }

        return $this->error('Refresh token not found', null, null, 404);
    }

    }

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Users as User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function register(Request $request): JsonResponse
    {
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

            $token = JWTAuth::fromUser($user);
            $apiKey = $user->generateApiKey();
            return response()->json([
                'message' => 'User successfully registered',
                'data' => [
                    'user' => $user->makeHidden(['password', 'remember_token']),
                    'token' => $token,
                    'authorization' => [
                        'type' => 'bearer',
                    ],
                    'api_key' => $apiKey,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'User registration failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();

        if (!$user->is_active) {
            return response()->json(['message' => 'User is not active'], 403);
        }

        // Generate API key if user doesn't have one
        if (!$user->api_key) {
            $user->generateApiKey();
        }

        return response()->json([
            'message' => 'User successfully logged in',
            'data' => [
                'user' => $user->makeHidden(['password', 'remember_token']),
                'token' => $token,
                'authorization' => [
                    'type' => 'bearer',
                ],
                'api_key' => $user->api_key,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        JwtAuth::logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'message' => 'Token refreshed',
                'data' => [
                    'authorization' => [
                    'token' => $newToken,
                    'type' => 'bearer',
                ],
                ],
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->makeHidden(['password', 'remember_token']));
    }

    public function sendEmailVerification(Request $request)
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent']);
    }

    public function verifyEmail(Request $request)
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);        

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent']);
        } else {
            return response()->json(['message' => 'Unable to send reset link'], 500);
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
            ? redirect()->route('login')->with('status',__(status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    public function generateApiKey(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $apiKey = $user->generateApiKey();

        return response()->json([
            'message' => 'API key generated successfully',
            'api_key' => $apiKey,
        ]);
    }
}

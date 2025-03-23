<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use App\Traits\CamelCaseAttributes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    use CamelCaseAttributes;

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $this->userService->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'is_admin' => false, // Default to regular user
        ]);

        return response()->json(['user' => $user->toApiResponse()], 201);
    }

    /**
     * Login a user and return a token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        \Log::info('Login attempt', ['request' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                \Log::warning('Invalid credentials');
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $user = User::where('email', $request->email)->first();
            \Log::info('User authenticated', ['user_id' => $user->id]);

            // Create token for API authentication
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'user' => $user->toApiResponse(),
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            \Log::error('Authentication error', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'Authentication error', 'debugTrace' => $e->getMessage()], 500);
        }
    }

    /**
     * Log out the current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Send a password reset link.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $token = $this->userService->createPasswordResetToken($request->email);

        if (!$token) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // TODO: Send email with reset link
        // In a real application, you would use Mail facade
        // For simplicity, we'll just return the token

        return response()->json([
            'message' => 'Password reset link sent',
            'token' => $token, // In production, you would not return this
        ]);
    }

    /**
     * Reset the user's password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $success = $this->userService->resetPassword(
            $request->email,
            $request->token,
            $request->password
        );

        if (!$success) {
            return response()->json(['message' => 'Invalid token or email'], 400);
        }

        return response()->json(['message' => 'Password reset successfully']);
    }
}
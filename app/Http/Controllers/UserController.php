<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Traits\CamelCaseAttributes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    use CamelCaseAttributes;
    
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth:sanctum');
        $this->middleware('admin')->except(['show', 'update']);
    }

    /**
     * Display a listing of the users.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            \Log::info('UserController@index called', [
                'auth_user' => auth()->user() ? auth()->user()->id : 'Not authenticated',
                'all_users_count' => count($this->userService->all())
            ]);
            
            $users = $this->userService->all();
            $camelCaseUsers = $users->map(function ($user) {
                return $user->toApiResponse();
            });
            return response()->json(['users' => $camelCaseUsers]);
        } catch (\Exception $e) {
            \Log::error('UserController@index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Store a newly created user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $this->userService->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'is_admin' => $request->is_admin ?? false,
        ]);

        return response()->json(['user' => $user->toApiResponse()], 201);
    }

    /**
     * Display the specified user.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function show(Request $request, string $id): JsonResponse
    {
        // Regular users can only see their own profile
        if ($id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = $this->userService->findById($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['user' => $user->toApiResponse()]);
    }

    /**
     * Update the specified user.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // Regular users can only update their own profile
        if ($id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'password' => ['sometimes', 'confirmed', Rules\Password::defaults()],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Regular users cannot set themselves as admin
        if (isset($request->is_admin) && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized to change admin status'], 403);
        }

        $user = $this->userService->update($id, $request->only(['name', 'email', 'password', 'is_admin']));

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['user' => $user->toApiResponse()]);
    }

    /**
     * Remove the specified user.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $success = $this->userService->delete($id);

        if (!$success) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['message' => 'User deleted successfully']);
    }
}
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FileStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    private FileStore $fileStore;

    public function __construct(FileStore $fileStore)
    {
        $this->fileStore = $fileStore;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all files for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $ownedFiles = $this->fileStore->getFilesByOwner($user->id);
        $sharedFiles = $this->fileStore->getSharedFiles($user->id);

        return response()->json([
            'owned_files' => array_map(function ($file) {
                return $file->toArray();
            }, $ownedFiles),
            'shared_files' => array_map(function ($file) {
                return $file->toArray();
            }, $sharedFiles),
        ]);
    }

    /**
     * Store a newly uploaded file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $this->fileStore->store($request->file('file'), $request->user()->id);

        return response()->json($file->toArray(), 201);
    }

    /**
     * Display the specified file.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function show(Request $request, string $id)
    {
        $file = $this->fileStore->get($id);

        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $user = $request->user();

        // Check permissions
        if (!$file->hasPermission($user->id, 'read') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Return file info
        return response()->json($file->toArray());
    }

    /**
     * Download the specified file.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Request $request, string $id)
    {
        $file = $this->fileStore->get($id);

        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $user = $request->user();

        // Check permissions
        if (!$file->hasPermission($user->id, 'read') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Return file for download
        if (!Storage::disk('local')->exists($file->path)) {
            return response()->json(['message' => 'File not found on disk'], 404);
        }

        return Storage::disk('local')->download($file->path, $file->name);
    }

    /**
     * Update the specified file.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $this->fileStore->get($id);

        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $user = $request->user();

        // Check permissions
        if (!$file->hasPermission($user->id, 'write') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $updatedFile = $this->fileStore->update($id, $request->only(['name']));

        return response()->json($updatedFile->toArray());
    }

    /**
     * Remove the specified file.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $file = $this->fileStore->get($id);

        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $user = $request->user();

        // Check permissions
        if (!$file->hasPermission($user->id, 'delete') && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->fileStore->delete($id);

        return response()->json(['message' => 'File deleted successfully']);
    }

    /**
     * Share a file with another user.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function share(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'string'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'string', 'in:read,write,delete'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $this->fileStore->get($id);

        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $user = $request->user();

        // Only file owner or admin can share
        if ($file->owner_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $success = $this->fileStore->shareFile($id, $request->user_id, $request->permissions);

        if (!$success) {
            return response()->json(['message' => 'Failed to share file'], 500);
        }

        return response()->json(['message' => 'File shared successfully']);
    }

    /**
     * Remove file sharing for a user.
     *
     * @param Request $request
     * @param string $id
     * @param string $userId
     * @return JsonResponse
     */
    public function removeShare(Request $request, string $id, string $userId): JsonResponse
    {
        $file = $this->fileStore->get($id);

        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $user = $request->user();

        // Only file owner or admin can remove share
        if ($file->owner_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $success = $this->fileStore->removeFileShare($id, $userId);

        if (!$success) {
            return response()->json(['message' => 'Failed to remove sharing'], 500);
        }

        return response()->json(['message' => 'Sharing removed successfully']);
    }
}
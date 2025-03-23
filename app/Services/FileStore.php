<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileStore
{
    private string $storageDir;

    /**
     * Create a new FileStore instance.
     */
    public function __construct()
    {
        $this->storageDir = storage_path('app/files');

        // Ensure storage directory exists
        if (!file_exists($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Store a new file.
     *
     * @param UploadedFile $uploadedFile
     * @param string $ownerId
     * @return File
     */
    public function store(UploadedFile $uploadedFile, string $ownerId): File
    {
        // Store physical file
        $fileName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $fileExt = $uploadedFile->getClientOriginalExtension();
        $uniqueName = $fileName . '_' . uniqid() . '.' . $fileExt;
        $storagePath = $uploadedFile->storeAs('files', $uniqueName, 'local');

        // Create file record
        $file = File::create([
            'name' => $uploadedFile->getClientOriginalName(),
            'path' => $storagePath,
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'owner_id' => $ownerId,
            'shared_with' => [],
            'permissions' => [],
        ]);

        return $file;
    }

    /**
     * Get a file by ID.
     *
     * @param string $id
     * @return File|null
     */
    public function get(string $id): ?File
    {
        return File::find($id);
    }

    /**
     * Get all files owned by a user.
     *
     * @param string $ownerId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFilesByOwner(string $ownerId)
    {
        return File::where('owner_id', $ownerId)->get();
    }

    /**
     * Get all files shared with a user.
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSharedFiles(string $userId)
    {
        // SQL LIKE query to find shared_with containing userId
        return File::where('shared_with', 'LIKE', '%"' . $userId . '"%')->get();
    }

    /**
     * Update a file's metadata.
     *
     * @param string $id
     * @param array $data
     * @return File|null
     */
    public function update(string $id, array $data): ?File
    {
        $file = $this->get($id);
        if (!$file) {
            return null;
        }

        // Update file properties
        if (isset($data['name'])) {
            $file->name = $data['name'];
        }

        if (isset($data['shared_with'])) {
            $file->shared_with = $data['shared_with'];
        }

        $file->save();

        return $file;
    }

    /**
     * Delete a file.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $file = $this->get($id);
        if (!$file) {
            return false;
        }

        // Delete physical file
        Storage::disk('local')->delete($file->path);

        // Delete from database
        return (bool) $file->delete();
    }

    /**
     * Share a file with a user.
     *
     * @param string $fileId
     * @param string $userId
     * @param array $permissions
     * @return bool
     */
    public function shareFile(string $fileId, string $userId, array $permissions): bool
    {
        $file = $this->get($fileId);
        if (!$file) {
            return false;
        }

        $file->shareWith($userId, $permissions);
        
        return true;
    }

    /**
     * Remove file sharing for a user.
     *
     * @param string $fileId
     * @param string $userId
     * @return bool
     */
    public function removeFileShare(string $fileId, string $userId): bool
    {
        $file = $this->get($fileId);
        if (!$file) {
            return false;
        }

        $file->removeShare($userId);
        
        return true;
    }
}
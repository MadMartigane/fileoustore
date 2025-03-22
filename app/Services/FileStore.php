<?php

declare(strict_types=1);

namespace App\Services;

use DateTime;
use App\Models\File;
use SleekDB\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileStore
{
    private Store $store;
    private string $storageDir;

    /**
     * Create a new FileStore instance.
     */
    public function __construct()
    {
        $databaseDir = storage_path('sleekdb');
        
        // Ensure the directory exists
        if (!file_exists($databaseDir)) {
            mkdir($databaseDir, 0755, true);
        }
        
        $this->store = new Store('files', $databaseDir, [
            'auto_cache' => true,
            'timeout' => false,
        ]);
        
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
        $file = new File([
            'name' => $uploadedFile->getClientOriginalName(),
            'path' => $storagePath,
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'owner_id' => $ownerId,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ]);

        // Save to SleekDB
        $data = $file->toArray();
        $result = $this->store->insert($data);
        
        return File::fromArray($result);
    }

    /**
     * Get a file by ID.
     *
     * @param string $id
     * @return File|null
     */
    public function get(string $id): ?File
    {
        $result = $this->store->findById($id);
        if (!$result) {
            return null;
        }

        return File::fromArray($result);
    }

    /**
     * Get all files owned by a user.
     *
     * @param string $ownerId
     * @return array
     */
    public function getFilesByOwner(string $ownerId): array
    {
        $results = $this->store->createQueryBuilder()
            ->where([['owner_id', '=', $ownerId]])
            ->getQuery()
            ->fetch();
            
        $files = [];

        foreach ($results as $result) {
            $files[] = File::fromArray($result);
        }

        return $files;
    }

    /**
     * Get all files shared with a user.
     *
     * @param string $userId
     * @return array
     */
    public function getSharedFiles(string $userId): array
    {
        // SleekDB doesn't have a direct way to search in arrays, so we need to get all files
        // and filter them in PHP
        $allFiles = $this->store->findAll();
        $sharedFiles = [];

        foreach ($allFiles as $fileData) {
            if (isset($fileData['shared_with'][$userId])) {
                $sharedFiles[] = File::fromArray($fileData);
            }
        }

        return $sharedFiles;
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

        $file->updated_at = new DateTime();
        
        // Save to SleekDB
        $this->store->update($file->toArray());
        
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
        
        // Delete from SleekDB
        $this->store->deleteById($id);
        
        return true;
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
        $this->store->update($file->toArray());
        
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
        $this->store->update($file->toArray());
        
        return true;
    }
}
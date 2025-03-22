<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;

class File
{
    public string $id;
    public string $name;
    public string $path;
    public string $mime_type;
    public int $size;
    public string $owner_id;
    public array $shared_with = [];
    public array $permissions = [];
    public DateTime $created_at;
    public DateTime $updated_at;

    /**
     * Create a new File instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->id = $attributes['id'] ?? uniqid('file_');
        $this->name = $attributes['name'] ?? '';
        $this->path = $attributes['path'] ?? '';
        $this->mime_type = $attributes['mime_type'] ?? '';
        $this->size = $attributes['size'] ?? 0;
        $this->owner_id = $attributes['owner_id'] ?? '';
        $this->shared_with = $attributes['shared_with'] ?? [];
        $this->permissions = $attributes['permissions'] ?? [];
        $this->created_at = $attributes['created_at'] ?? new DateTime();
        $this->updated_at = $attributes['updated_at'] ?? new DateTime();
    }

    /**
     * Convert to array for storage.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'owner_id' => $this->owner_id,
            'shared_with' => $this->shared_with,
            'permissions' => $this->permissions,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Create from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $file = new self();
        $file->id = $data['id'] ?? uniqid('file_');
        $file->name = $data['name'] ?? '';
        $file->path = $data['path'] ?? '';
        $file->mime_type = $data['mime_type'] ?? '';
        $file->size = $data['size'] ?? 0;
        $file->owner_id = $data['owner_id'] ?? '';
        $file->shared_with = $data['shared_with'] ?? [];
        $file->permissions = $data['permissions'] ?? [];
        $file->created_at = isset($data['created_at']) ? new DateTime($data['created_at']) : new DateTime();
        $file->updated_at = isset($data['updated_at']) ? new DateTime($data['updated_at']) : new DateTime();

        return $file;
    }

    /**
     * Check if a user has permission for a specific action on this file.
     *
     * @param string $userId
     * @param string $permission (read, write, delete)
     * @return bool
     */
    public function hasPermission(string $userId, string $permission): bool
    {
        // Owner has all permissions
        if ($userId === $this->owner_id) {
            return true;
        }

        // Check shared permissions
        if (isset($this->shared_with[$userId])) {
            return in_array($permission, $this->shared_with[$userId], true);
        }

        return false;
    }

    /**
     * Share file with a user.
     *
     * @param string $userId
     * @param array $permissions
     * @return void
     */
    public function shareWith(string $userId, array $permissions): void
    {
        $this->shared_with[$userId] = $permissions;
        $this->updated_at = new DateTime();
    }

    /**
     * Remove share from a user.
     *
     * @param string $userId
     * @return void
     */
    public function removeShare(string $userId): void
    {
        if (isset($this->shared_with[$userId])) {
            unset($this->shared_with[$userId]);
            $this->updated_at = new DateTime();
        }
    }
}
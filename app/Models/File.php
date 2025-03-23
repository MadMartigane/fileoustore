<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\CamelCaseAttributes;

class File extends Model
{
    use CamelCaseAttributes;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files';
    
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
    
    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'path',
        'mime_type',
        'size',
        'owner_id',
        'shared_with',
        'permissions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'size' => 'integer',
        'shared_with' => 'array',
        'permissions' => 'array',
    ];

    /**
     * Boot function to set ID if not provided
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = 'file_' . uniqid();
            }
            if (empty($model->shared_with)) {
                $model->shared_with = [];
            }
            if (empty($model->permissions)) {
                $model->permissions = [];
            }
        });
    }

    /**
     * Get the user that owns the file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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
        $sharedWith = $this->shared_with ?? [];
        
        if (isset($sharedWith[$userId])) {
            return in_array($permission, $sharedWith[$userId], true);
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
        $sharedWith = $this->shared_with ?? [];
        $sharedWith[$userId] = $permissions;
        $this->shared_with = $sharedWith;
        $this->save();
    }

    /**
     * Remove share from a user.
     *
     * @param string $userId
     * @return void
     */
    public function removeShare(string $userId): void
    {
        $sharedWith = $this->shared_with ?? [];
        
        if (isset($sharedWith[$userId])) {
            unset($sharedWith[$userId]);
            $this->shared_with = $sharedWith;
            $this->save();
        }
    }
    
    /**
     * Convert to camelCase array for API responses.
     *
     * @return array
     */
    public function toApiResponse(): array
    {
        return $this->snakeToCamel($this->toArray());
    }
}
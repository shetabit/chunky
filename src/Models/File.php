<?php

namespace Shetabit\Chunky\Models;

use Illuminate\Database\Eloquent\Model;
use Shetabit\Chunky\Traits\UsesUuid;

class File extends Model
{
    use UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'client_name', 'client_extension',
        'name', 'extension', 'size',
        'path', 'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'size' => 'int',
        'meta' => 'json',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the owning visitable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function chunks()
    {
        return $this->hasMany(Chunk::class);
    }

    /**
     * Determine if file has uploaded completely
     *
     * @return bool
     */
    public function hasCompleted() : bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Determine if file has not yet uploaded completely
     *
     * @return bool
     */
    public function hasNotCompleted() : bool
    {
        return $this->completed_at === null;
    }

    /**
     * Mark file as completely uploaded.
     *
     * @return $this
     */
    public function markAsCompleted()
    {
        $this->forceFill(['completed_at' => now()])->save();

        return $this;
    }

    /**
     * Mark file as uncompletely uploaded.
     * 
     * @return $this
     */
    public function markAsUncompleted()
    {
        $this->forceFill(['completed_at' => null])->save();

        return $this;
    }

    /**
     * Filter uncompleted file uploads.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Filter completed file uploads.
     */
    public function scopeUncompleted($query)
    {
        return $query->whereNull('completed_at');
    }
}

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
        'name', 'extension', 'size', 'completed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'size' => 'int',
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
}

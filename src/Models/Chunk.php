<?php

namespace Shetabit\Chunky\Models;

use Illuminate\Database\Eloquent\Model;
use Shetabit\Chunky\Models\File;
use Shetabit\Chunky\Traits\UsesUuid;

class Chunk extends Model
{
    use UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chunks';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'size', 'offset', 'file_uuid',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'size' => 'int',
        'offset' => 'int',
    ];

    /**
     * Get the owning visitable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_uuid');
    }
}

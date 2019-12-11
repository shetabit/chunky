<?php

namespace Shetabit\Chunky\Contracts;

use Shetabit\Chunky\Models\Chunk;
use Shetabit\Chunky\Models\File;

interface InputStreamInterface
{
    /**
     * Retrieve and store chunks
     *
     * @param $data
     *
     * @return Chunk
     */
    public function store($data) : Chunk;

    /**
     * Retrieve stream status
     *
     * @param string $fileId
     *
     * @return array
     */
    public function status(string $fileId) : array;
}

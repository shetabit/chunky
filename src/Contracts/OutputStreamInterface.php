<?php

namespace Shetabit\Chunky\Contracts;

use Shetabit\Chunky\Models\Chunk;
use Shetabit\Chunky\Models\File;

interface OutputStreamInterface
{
    /**
     * Set request's input name
     *
     * @param string $name
     * 
     * @return $this
     */
    public function setInputName(string $name);

    /**
     * Retrieve request's input name
     *
     *
     * @return string|null
     */
    public function getInputName() : ?string;

    /**
     * Register file in database
     * 
     * @param callable|null $decoder
     *
     * @return File
     */
    public function register(callable $decoder = null) : File;

    /**
     * Determine if file has been registered or not
     *
     * @param string $id
     *
     * @return bool
     */
    public function hasRegistered($id) : bool;

    /**
     * Retrieve and store chunks
     *
     * @param callable|null $decoder
     *
     * @return Chunk
     */
    public function store(callable $decoder = null) : Chunk;

    /**
     * Check if upload has completed
     *
     * @param string $id
     *
     * @return bool
     */
    public function hasCompleted($id) : bool;
}

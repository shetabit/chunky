<?php

namespace Shetabit\Chunky;

use Shetabit\Chunky\Contracts\ChunkInterface;

class Chunk implements ChunkInterface
{
    /**
     * Chunk's data
     */
    protected $data;

    /**
     * Chunk's offset
     *
     * @var int
     */
    protected $offset;

    /**
     * Chunk's size
     *
     * @var int
     */
    protected $size;

    /**
     * Chunk constructor
     */
    public function __construct($data, $size, $offset = 0)
    {
        $this->data = $data;
        $this->size = $size;
        $this->offset = $offset;
    }

    /**
     * Retrieve chunk's data.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Retrieve chunk's offset.
     *
     * @return int
     */
    public function getOffset() : int
    {
        return $this->offset;
    }

    /**
     * Retrieve chunk's size.
     *
     * @return int
     */
    public function getSize() : int
    {
        return $this->size;
    }
}

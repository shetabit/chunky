<?php

namespace Shetabit\Chunky\Contracts;

interface ChunkInterface
{
    /**
     * Retrieve chunk's data.
     */
    public function getData();

    /**
     * Retrieve chunk's Offset.
     *
     * @return int
     */
    public function getOffset() : int;

    /**
     * Retrieve chunk's size.
     *
     * @return int
     */
    public function getSize() : int;
}

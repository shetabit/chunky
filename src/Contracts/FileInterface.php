<?php

namespace Shetabit\Chunky\Contracts;

interface FileInterface
{
    /**
     * Create (or overwrite) new file
     *
     * @param $data
     * @return $this
     */
    public function create($data);

    /**
     * read file's data
     *
     * @param int $offset
     * @param null $length
     * @return bool|string
     */
    public function read($offset = 0, $length = null);

    /**
     * write data in file
     *
     * @param $data
     * @param int $offset
     * @return $this
     */
    public function write($data, $offset = 0);

    /**
     * Remove file if already exists
     *
     * @return $this
     */
    public function remove();
}

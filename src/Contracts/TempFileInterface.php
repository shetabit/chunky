<?php

namespace Shetabit\Chunky\Contracts;

/**
 * @property mixed $name  the name of the file
 * @property mixed $mime  the content type of the file
 * @property mixed $size  the size of the whole file in bytes
 * @property mixed $range the bytes of the whole file this one holds, `1024-2047`
 */
interface TempFileInterface extends FileInterface
{
    /**
     * Copy the content of this file into the file at the given path.
     *
     * @param string   $path   where the data is written to
     * @param int      $offset the byte of the destination the data is written at
     * @param int|null $length how many bytes of this file to copy, `null` copies all of them
     */
    public function saveAs(string $path, int $offset = 0, int|null $length = null) : static;
}

<?php

namespace Shetabit\Chunky\Contracts;

/**
 * A file carries the attribute information that came in with it, read and written
 * through `__get()` and `__set()`. The ones below are what the package itself sets
 * and reads; they hold whatever the client sent, hence `mixed`.
 *
 * @property mixed $name  the name of the file
 * @property mixed $mime  the content type of the file
 * @property mixed $size  the size of the whole file in bytes
 * @property mixed $range the bytes of the whole file this one holds, `1024-2047`
 */
interface FileInterface
{
    public function getPath() : string;

    public function exists() : bool;

    public function getSize() : int;

    public function create(string $data) : static;

    /**
     * @param int|null $length `null` reads until the end of the file
     */
    public function read(int $offset = 0, int|null $length = null) : string;

    /**
     * A write past the end of the file leaves a gap, which is what an upload that
     * receives its chunks out of order relies on.
     */
    public function write(string $data, int $offset = 0) : static;

    public function close() : static;

    public function remove() : static;
}

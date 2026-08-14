<?php

namespace Shetabit\Chunky\Contracts;

/**
 * Next to its content, a file carries the attribute information that came in with it.
 * Every attribute is read and written through `__get()` and `__set()`, and the ones
 * below are the ones the package sets and reads itself. They hold whatever the client
 * sent, which is why none of them is typed any narrower than `mixed`.
 *
 * @property mixed $name  the name of the file
 * @property mixed $mime  the content type of the file
 * @property mixed $size  the size of the whole file in bytes
 * @property mixed $range the bytes of the whole file this one holds, `1024-2047`
 */
interface FileInterface
{
    /**
     * The path the file is stored under.
     */
    public function getPath() : string;

    /**
     * Determine whether the file exists on disk.
     */
    public function exists() : bool;

    /**
     * The size of the file in bytes.
     */
    public function getSize() : int;

    /**
     * Create (or overwrite) the file with the given data.
     */
    public function create(string $data) : static;

    /**
     * Read (a part of) the file.
     *
     * @param int      $offset the byte the reading starts at
     * @param int|null $length how many bytes to read, `null` reads until the end of the file
     */
    public function read(int $offset = 0, int|null $length = null) : string;

    /**
     * Write data into the file, starting at the given byte.
     *
     * Everything that is already there stays where it is: a write past the end of the
     * file leaves a gap, which is what an upload that receives its chunks out of order
     * relies on.
     */
    public function write(string $data, int $offset = 0) : static;

    /**
     * Close the handle of the file, if it is open.
     */
    public function close() : static;

    /**
     * Remove the file, if it exists.
     */
    public function remove() : static;
}

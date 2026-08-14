<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\TempFileInterface;
use Shetabit\Chunky\Exceptions\FileAccessException;

class TempFile extends File implements TempFileInterface
{
    /**
     * Create (or overwrite) the temporary file with the given data.
     *
     * A temporary file that has no path of its own receives one here, and the file
     * behind it is removed by PHP as soon as its handle is closed.
     */
    public function create(string $data) : static
    {
        if ($this->getPath() === '') {
            $this->openTemporary();
        }

        return parent::create($data);
    }

    /**
     * Copy the content of this file into the file at the given path.
     *
     * This file holds a chunk, `$offset` says where that chunk belongs in the file
     * that is being assembled: the chunk is always read from its own beginning and
     * written at the offset of the destination.
     *
     * @param string   $path   where the data is written to
     * @param int      $offset the byte of the destination the data is written at
     * @param int|null $length how many bytes of this file to copy, `null` copies all of them
     */
    public function saveAs(string $path, int $offset = 0, int|null $length = null) : static
    {
        $data = $this->read(0, $length);

        new File($path)->write($data, $offset)->close();

        return $this;
    }

    /**
     * Open a file in temporary mode.
     *
     * Temporary files are removed automatically as soon as their handle is closed,
     * which happens at the latest when the script ends.
     *
     * @return resource
     */
    protected function openTemporary()
    {
        if (is_resource($this->handle)) {
            return $this->handle;
        }

        $handle = tmpfile();

        if ($handle === false) {
            throw new FileAccessException('A temporary file could not be created.');
        }

        $uri = stream_get_meta_data($handle)['uri'] ?? null;

        if (! is_string($uri) || $uri === '') {
            fclose($handle);

            throw new FileAccessException('The temporary file that was created has no path.');
        }

        $this->setPath($uri);

        return $this->handle = $handle;
    }
}

<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\TempFileInterface;
use Shetabit\Chunky\Exceptions\FileAccessException;

class TempFile extends File implements TempFileInterface
{
    /**
     * A temporary file without a path of its own receives one here, and PHP removes
     * the file behind it as soon as its handle is closed.
     */
    public function create(string $data) : static
    {
        if ($this->getPath() === '') {
            $this->openTemporary();
        }

        return parent::create($data);
    }

    /**
     * The chunk is read from its own beginning and written at the offset of the
     * destination, which is where it belongs in the file being assembled.
     *
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

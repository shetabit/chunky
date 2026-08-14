<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\FileInterface;
use Shetabit\Chunky\Exceptions\FileAccessException;
use Shetabit\Chunky\Exceptions\FileNotFoundException;

/**
 * @property mixed $name  the name of the file
 * @property mixed $mime  the content type of the file
 * @property mixed $size  the size of the whole file in bytes
 * @property mixed $range the bytes of the whole file this one holds, `1024-2047`
 */
class File implements FileInterface
{
    /**
     * The mode the file is opened in.
     *
     * `c+` opens the file for reading and writing, creates it when it is not there
     * yet and — unlike `w+` — keeps what is already in it. The write position can be
     * moved freely, which is what writing a chunk at its own offset needs.
     */
    protected const string MODE = 'c+b';

    /**
     * Where the file is stored.
     */
    protected string $path = '';

    /**
     * The handle of the file while it is open.
     *
     * @var resource|null
     */
    protected $handle;

    /**
     * The attribute information of the file.
     *
     * @example name, mime, size, range, ...
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * File constructor.
     */
    public function __construct(string $path = '')
    {
        $this->setPath($path);
    }

    /**
     * Retrieve the path of the file.
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * Determine whether the file exists on disk.
     */
    public function exists() : bool
    {
        return $this->path !== '' && is_file($this->path);
    }

    /**
     * The size of the file in bytes.
     *
     * It is read from the open handle instead of `filesize()`, which is answered from
     * a cache that a write of this very file does not clear.
     */
    public function getSize() : int
    {
        if (! $this->exists()) {
            return 0;
        }

        $stat = fstat($this->open());

        return $stat === false ? 0 : $stat['size'];
    }

    /**
     * Create (or overwrite) the file with the given data.
     */
    public function create(string $data) : static
    {
        $handle = $this->open();

        ftruncate($handle, 0);
        rewind($handle);
        $this->put($handle, $data);

        return $this;
    }

    /**
     * Read (a part of) the file.
     *
     * @param int      $offset the byte the reading starts at
     * @param int|null $length how many bytes to read, `null` reads until the end of the file
     */
    public function read(int $offset = 0, int|null $length = null) : string
    {
        if (! $this->exists()) {
            throw FileNotFoundException::forPath($this->path);
        }

        $size = $this->getSize();
        $offset = max(0, $offset);

        // A length that is not given, not positive or longer than the file itself
        // reads whatever is left of the file.
        if ($length === null || $length <= 0 || $length > $size) {
            $length = $size;
        }

        $length = min($length, $size - $offset);

        if ($length <= 0) { // the offset is at (or past) the end of the file
            return '';
        }

        $handle = $this->open();

        fseek($handle, $offset);

        return (string) fread($handle, $length);
    }

    /**
     * Write data into the file, starting at the given byte.
     *
     * Everything that is already there stays where it is: a write past the end of the
     * file leaves a gap, which is what an upload that receives its chunks out of order
     * relies on.
     */
    public function write(string $data, int $offset = 0) : static
    {
        $handle = $this->open();

        // lock stream
        flock($handle, LOCK_EX);

        fseek($handle, max(0, $offset));

        $this->put($handle, $data);

        // unlock stream
        flock($handle, LOCK_UN);

        return $this;
    }

    /**
     * Close the handle of the file, if it is open.
     */
    public function close() : static
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }

        $this->handle = null;

        return $this;
    }

    /**
     * Remove the file, if it exists.
     */
    public function remove() : static
    {
        $this->close();

        if ($this->exists()) {
            unlink($this->path);
        }

        return $this;
    }

    /**
     * Determine whether an attribute information exists.
     */
    public function __isset(string $name) : bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Set an attribute information.
     */
    public function __set(string $name, mixed $value) : void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Retrieve an attribute information.
     */
    public function __get(string $name) : mixed
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Remove an attribute information.
     */
    public function __unset(string $name) : void
    {
        unset($this->attributes[$name]);
    }

    /**
     * Retrieve every attribute information of the file.
     *
     * @return array<string, mixed>
     */
    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * File destructor.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Set or change the path of the file.
     */
    protected function setPath(string $path) : static
    {
        if ($path !== $this->path) { // the handle that is open belongs to the old path
            $this->close();
        }

        $this->path = $path;

        return $this;
    }

    /**
     * Open the file and keep its handle around.
     *
     * A single handle is kept and reused, so that repeated reads and writes of the
     * same file neither reopen it nor leave a handle behind.
     *
     * @return resource
     */
    protected function open()
    {
        if (is_resource($this->handle)) {
            return $this->handle;
        }

        if ($this->path === '') {
            throw new FileAccessException('The file has no path to be opened under.');
        }

        $handle = @fopen($this->path, static::MODE);

        if ($handle === false) {
            throw FileAccessException::forPath($this->path);
        }

        return $this->handle = $handle;
    }

    /**
     * Write data through the given handle and make sure it reaches the file.
     *
     * @param resource $handle
     */
    protected function put($handle, string $data) : void
    {
        if (fwrite($handle, $data) === false) {
            throw new FileAccessException(sprintf('The file "%s" could not be written to.', $this->path));
        }

        fflush($handle);
    }
}

<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\FileInterface;

class File implements FileInterface
{
    /**
     * store file path
     *
     * @var
     */
    protected $path;

    /**
     * currently open handles
     *
     * @var array
     */
    protected $handles = [];


    /**
     * stores file's attribute information
     * @example : name, mime, extension ,...
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * File constructor.
     *
     * @param null $path
     */
    public function __construct($path = null)
    {
        $this->setPath($path);
    }

    /**
     * Set or change the file's path
     *
     * @param $path
     * @return mixed
     */
    protected function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Retrieve file's path
     *
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Create (or overwrite) new file
     *
     * @param $data
     * @return $this
     */
    public function create($data)
    {
        $handle = $this->open('w+');

        fwrite($handle, $data);

        return $this;
    }

    /**
     * read file's data
     *
     * @param int $offset
     * @param null $length
     * @return bool|string
     */
    public function read($offset = 0, $length = null)
    {
        $offset = $offset ?? 0;
        $fileSize = filesize($this->getPath());
        $length = ($length<=0 || $length>$fileSize) ? $fileSize : $length;

        $handle = $this->open('r+');

        if ($offset) {
            fseek($handle, $offset);
        }

        return fread($handle, $length);
    }

    /**
     * write data in file
     *
     * @param $data
     * @param int $offset
     * @return $this
     */
    public function write($data, $offset = 0)
    {
        $handle = $this->open('a');

        fseek($handle, (int) $offset, SEEK_SET);

        // lock stream
        flock($handle, LOCK_EX);

        fwrite($handle, $data);

        // unlock stream
        flock($handle, LOCK_UN);

        return $this;
    }

    /**
     * Remove file if already exists
     *
     * @return $this
     */
    public function remove()
    {
        $path = $this->getPath();

        if (file_exists($path)) {
            unlink($path);
        }

        return $this;
    }

    /**
     * Open file and create a new handle
     *
     * @param $mode
     * @return bool|resource
     */
    protected function open($mode)
    {
         $handle = fopen($this->getPath(), $mode);

         array_push($this->handles, $handle);

        return $handle;
    }

    /**
     * Close given file handles
     *
     * @param $handle
     * @return $this
     */
    protected function close($handle)
    {
        fclose($handle);

        return $this;
    }

    /**
     * Close all file handles
     *
     * @return $this
     */
    protected function closeAll()
    {
        foreach ($this->handles as $handle) {
            $this->close($handle);
        }

        return $this;
    }

    /**
     * Determine if an attribute information exists
     *
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Set an attribute information
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Retrieve an attribute information
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * File destructor.
     */
    public function __destruct()
    {
        $this->closeAll();
    }
}

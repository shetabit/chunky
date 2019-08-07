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
     * currently open handlers
     *
     * @var array
     */
    protected $handlers = [];


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
        return $this->path = $path;
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
        $handler = $this->open('w+');

        fwrite($handler, $data);

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
        $length = $length ?? filesize($this->getPath());

        $handler = $this->open('r+');

        if ($offset) {
            fseek($handler, $offset);
        }

        return fread($handler, $length);
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
        $handler = $this->open('a+');

        if ($offset) {
            fseek($handler, $offset);
        }

        fwrite($handler, $data);

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
     * Open file and create a new handler
     *
     * @param $mode
     * @return bool|resource
     */
    protected function open($mode)
    {
         $handler = fopen($this->getPath(), $mode);

         array_push($this->handlers, $handler);

        return $handler;
    }

    /**
     * Close given file handlers
     *
     * @param $handler
     * @return $this
     */
    protected function close($handler)
    {
        fclose($handler);

        return $this;
    }

    /**
     * Close all file handlers
     *
     * @return $this
     */
    protected function closeAll()
    {
        foreach ($this->handlers as $handler) {
            $this->close($handler);
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

<?php

namespace Shetabit\Payment;

use Shetabit\Chunky\Contracts\InputStreamInterface;
use Shetabit\Chunky\Exceptions\FileHasNotRegisteredException;
use Shetabit\Chunky\Models\Chunk;
use Shetabit\Chunky\Models\File;
use Shetabit\Payment\Exceptions\DriverNotFoundException;

class InputStream
{
    /**
     * Stream Configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Stream Driver Settings.
     *
     * @var array
     */
    protected $settings;

    /**
     * Input's name.
     *
     * @var string
     */
    protected $inputName;

    /**
     * Chunk's size in bytes
     *
     * @var int
     */
    protected $chunkSize = 524288; // 512kb

    /**
     * Stream Driver Name.
     *
     * @var string
     */
    protected $driver;

    /**
     * Stream Driver Instance.
     *
     * @var object
     */
    protected $driverInstance;

    /**
     * InputStream constructor.
     *
     * @param $config
     *
     * @throws \Exception
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->via($this->config['default']);
    }

    /**
     * Set custom configs
     * we can use this method when we want to use dynamic configs
     *
     * @param $key
     * @param $value|null
     *
     * @return $this
     */
    public function config($key, $value = null)
    {
        $configs = [];

        $key = is_array($key) ? $key : [$key => $value];

        foreach ($key as $k => $v) {
            $configs[$k] = $v;
        }

        $this->settings = array_merge($this->settings, $configs);

        return $this;
    }

    /**
     * Set inputName.
     *
     * @param String $name|null
     *
     * @return $this
     */
    public function inputName(String $name = null)
    {
        $this->inputName = $name;

        return $this;
    }

    /**
     * Reset the inputName to its original that exists in configs.
     *
     * @return $this
     */
    public function resetInputName()
    {
        $this->inputName();

        return $this;
    }

    /**
     * Set chunk's size
     * 
     * @param int $size
     * 
     * @return $this
     */
    public function chunkSize($size)
    {
        $this->chunkSize = $size;

        return $this;
    }

    /**
     * Set chunk's size
     * 
     * @param int $size
     * 
     * @return $this
     */
    public function getChunkSize()
    {
        return $this->chunkSize;
    }

    /**
     * Change the driver on the fly.
     *
     * @param $driver
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function via($driver)
    {
        $this->driver = $driver;
        $this->validateDriver();
        $this->settings = $this->config['drivers'][$driver];

        return $this;
    }

    /**
     * Register file in database
     *
     * @param string $name
     * @param string $extension
     * @param string $size
     * @param string $storagePath
     *
     * @return File
     */
    public function register(string $fullName, string $size, string $storagePath = null)
    {
        $storagePath = $storagePath ?? public_path();
        $normalizedStoragePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, rtrim($storagePath, '\/\\'));
        $fileInfo = [];

        // set file's client informations
        $fileInfo['client_name'] = pathinfo($fullName, PATHINFO_FILENAME);
        $fileInfo['client_extension'] = pathinfo($fullName, PATHINFO_FILENAME);
        $fileInfo['size'] = $size;

        // set file's server informations
        $fileInfo['name'] = $this->createAUniqueName($normalizedStoragePath, $fileInfo);
        $fileInfo['extension'] = $fileInfo['client_extension'];
        $fileInfo['path'] = $normalizedStoragePath;

        $file = File::create($fileInfo);

        return [
        ];
    }

    /**
     * Determine if file has been registered or not
     *
     * @param string $id
     *
     * @return bool
     */
    public function hasRegistered($id) : bool
    {
        return File::where('id', '=', $id)->count() > 0;
    }

    /**
     * Retrieve and store chunks
     *
     * @param String $fileId
     * @param String $data
     * @param int $size
     * @param int $offset
     *
     * @return Chunk
     */
    public function store($fileId, $data, int $size, int $offset = 0) : Chunk
    {
        if (!$this->hasRegistered($fileId)) {
            throw new FileHasNotRegisteredException;
        }

        if($offset <= 0) {
            $offset = 0;
        }

        $chunkInfo = [
            'size' => $size,
            'offset' => $offset,
            'file_id' => $fileId,
        ];

        $chunk = Chunk::create($chunkInfo);

        return $this->getDriverInstance()->store($chunk, $data);
    }

    /**
     * Check if upload has completed
     *
     * @param string $id
     *
     * @return bool
     */
    public function hasCompleted($id) : bool
    {
        return File::completed()->where('id', '=', $id)->count() > 0;
    }

    /**
     * Retrieve stream status
     *
     * @param string $fileId
     *
     * @return array
     */
    public function status(string $fileId) : array
    {
        if (!$this->hasRegistered($fileId)) {
            throw new FileHasNotRegisteredException;
        }

        return $this->getDriverInstance()->status($fileId);
    }

    /**
     * Retrieve current driver instance or generate new one.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function getDriverInstance()
    {
        if (!empty($this->driverInstance)) {
            return $this->driverInstance;
        }

        return $this->getFreshDriverInstance();
    }

    /**
     * Get new driver instance
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function getFreshDriverInstance()
    {
        $this->validateDriver();
        $class = $this->config['map'][$this->driver];

        if (!empty($this->callbackUrl)) { // use custom callbackUrl if exists
            $this->settings['callbackUrl'] = $this->callbackUrl;
        }

        return new $class($this->invoice, $this->settings);
    }

    /**
     * Validate driver.
     *
     * @throws \Exception
     */
    protected function validateDriver()
    {
        if (empty($this->driver)) {
            throw new DriverNotFoundException('Driver not selected or default driver does not exist.');
        }

        if (empty($this->config['drivers'][$this->driver]) || empty($this->config['map'][$this->driver])) {
            throw new DriverNotFoundException('Driver not found in config file. Try updating the package.');
        }

        if (!class_exists($this->config['map'][$this->driver])) {
            throw new DriverNotFoundException('Driver source not found. Please update the package.');
        }

        $reflect = new \ReflectionClass($this->config['map'][$this->driver]);

        if (!$reflect->implementsInterface(InputStreamInterface::class)) {
            throw new \Exception("Driver must be an instance of Contracts\InputStreamInterface.");
        }
    }

    /**
     * Create a unique name in the given path
     *
     * @param string $path
     * @param array $normalizedFileInfo
     * 
     * @return string
     */
    protected function createAUniqueName(string $path, array $normalizedFileInfo)
    {
        $path = $path.DIRECTORY_SEPARATOR;
        $name = $normalizedFileInfo['name'];
        $extension = $normalizedFileInfo['extension'];
        $counter = 1;

        $storageFileName = $name.'.'.$extension;

        while(file_exists($path.$storageFileName)) {
            $storageFileName = $name.'_'.$counter.'.'.$extension;
            $counter++;
        }

        return $name.'_'.$counter;
    }
}

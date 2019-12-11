<?php

namespace Shetabit\Payment;

use Shetabit\Chunky\Contracts\InputStreamInterface;
use Shetabit\Chunky\Exceptions\FileHasNotRegisteredException;
use Shetabit\Chunky\Models\File;
use Shetabit\Payment\Exceptions\DriverNotFoundException;

class OutputStream
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

    public function processUsingFileId(string $fileId)
    {
        if (!$this->hasRegistered($fileId)) {
            throw new FileHasNotRegisteredException;
        }

        $file = File::where('id', '=', $fileId)->first();
        $path = $file->path.$file->name.'.'.$file->extensions;

        return $this->getDriverInstance()->process($path, request());
    }

    public function processUsingFilePath(string $path)
    {
        return $this->getDriverInstance()->process($path, request());
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
            throw new \Exception("Driver must be an instance of Contracts\OutputStreamInterface.");
        }
    }
}

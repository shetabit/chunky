<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Contracts\TempFileInterface;

trait HasUploadEvents
{
    /**
     * @var array<string, callable>
     */
    protected array $eventCallbacks = [];

    /**
     * @param callable(TempFileInterface): void $callback
     */
    public function beforeValidate(callable $callback) : static
    {
        return $this->on('beforeValidate', $callback);
    }

    /**
     * Called whether the chunk passed or not, with its errors as second argument.
     *
     * @param callable(TempFileInterface, list<string>): void $callback
     */
    public function afterValidate(callable $callback) : static
    {
        return $this->on('afterValidate', $callback);
    }

    /**
     * @param callable(TempFileInterface, string): void $callback second argument is the path
     */
    public function beforeUpload(callable $callback) : static
    {
        return $this->on('beforeUpload', $callback);
    }

    /**
     * The third argument is how much of the file is on disk now, which is how a run
     * finds out that the last chunk of a file has arrived.
     *
     * @param callable(TempFileInterface, string, int): void $callback
     */
    public function afterUpload(callable $callback) : static
    {
        return $this->on('afterUpload', $callback);
    }

    protected function on(string $event, callable $callback) : static
    {
        $this->eventCallbacks[$event] = $callback;

        return $this;
    }

    protected function dispatchEvent(string $event, mixed ...$arguments) : void
    {
        $callback = $this->eventCallbacks[$event] ?? null;

        if ($callback !== null) {
            $callback(...$arguments);
        }
    }
}

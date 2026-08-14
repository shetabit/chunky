<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Contracts\TempFileInterface;

trait HasUploadEvents
{
    /**
     * The callbacks that were registered, by the name of their event.
     *
     * @var array<string, callable>
     */
    protected array $eventCallbacks = [];

    /**
     * Called with every chunk before it is validated.
     *
     * @param callable(TempFileInterface): void $callback
     */
    public function beforeValidate(callable $callback) : static
    {
        return $this->on('beforeValidate', $callback);
    }

    /**
     * Called with every chunk after it was validated, whether it passed or not.
     *
     * @param callable(TempFileInterface, list<string>): void $callback the errors of
     *                                                                  this chunk are
     *                                                                  the second
     *                                                                  argument
     */
    public function afterValidate(callable $callback) : static
    {
        return $this->on('afterValidate', $callback);
    }

    /**
     * Called with every chunk before it is written, and with where it is written to.
     *
     * @param callable(TempFileInterface, string): void $callback
     */
    public function beforeUpload(callable $callback) : static
    {
        return $this->on('beforeUpload', $callback);
    }

    /**
     * Called with every chunk after it was written, with where it was written to and
     * with how much of the file is there now — which is how a run finds out that the
     * last chunk of a file has arrived.
     *
     * @param callable(TempFileInterface, string, int): void $callback
     */
    public function afterUpload(callable $callback) : static
    {
        return $this->on('afterUpload', $callback);
    }

    /**
     * Register a callback for the event of the given name.
     */
    protected function on(string $event, callable $callback) : static
    {
        $this->eventCallbacks[$event] = $callback;

        return $this;
    }

    /**
     * Call the callback of the given event, if there is one.
     */
    protected function dispatchEvent(string $event, mixed ...$arguments) : void
    {
        $callback = $this->eventCallbacks[$event] ?? null;

        if ($callback !== null) {
            $callback(...$arguments);
        }
    }
}

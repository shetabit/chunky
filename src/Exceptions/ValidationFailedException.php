<?php

namespace Shetabit\Chunky\Exceptions;

use Shetabit\Chunky\Contracts\FileInterface;

class ValidationFailedException extends ChunkyException
{
    /**
     * @var list<string>
     */
    protected array $errors;

    /**
     * Not `$file`, and neither is its getter: `Exception::getFile()` is final and
     * holds the file the exception was thrown in.
     */
    public function __construct(string $message, protected FileInterface|null $chunk = null)
    {
        parent::__construct($message);

        $this->errors = [$message];
    }

    /**
     * @param list<string> $errors
     */
    public static function fromErrors(array $errors) : self
    {
        $exception = new self(implode(PHP_EOL, $errors));

        $exception->errors = $errors;

        return $exception;
    }

    /**
     * @return list<string>
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * Only set when a single chunk was at fault.
     */
    public function getChunk() : FileInterface|null
    {
        return $this->chunk;
    }
}

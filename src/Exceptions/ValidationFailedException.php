<?php

namespace Shetabit\Chunky\Exceptions;

use Shetabit\Chunky\Contracts\FileInterface;

/**
 * A file was turned away by a validation.
 */
class ValidationFailedException extends ChunkyException
{
    /**
     * Everything that is wrong, one message per validation that failed.
     *
     * @var list<string>
     */
    protected array $errors;

    /**
     * @param FileInterface|null $chunk the file that was turned away. It is not called
     *                                  `$file`, and neither is its getter, because
     *                                  `Exception` has a `getFile()` of its own — a
     *                                  final one, holding the file the exception was
     *                                  thrown in.
     */
    public function __construct(string $message, protected FileInterface|null $chunk = null)
    {
        parent::__construct($message);

        $this->errors = [$message];
    }

    /**
     * The exception of a whole run: every file that was turned away, and why.
     *
     * @param list<string> $errors
     */
    public static function fromErrors(array $errors) : self
    {
        $exception = new self(implode(PHP_EOL, $errors));

        $exception->errors = $errors;

        return $exception;
    }

    /**
     * Everything that is wrong, one message per validation that failed.
     *
     * @return list<string>
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * The chunk that was turned away, when a single one was.
     */
    public function getChunk() : FileInterface|null
    {
        return $this->chunk;
    }
}

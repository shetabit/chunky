<?php

namespace Shetabit\Chunky\Exceptions;

/**
 * A file exists, but it could not be opened, written to or removed.
 */
class FileAccessException extends ChunkyException
{
    public static function forPath(string $path) : self
    {
        return new self(sprintf('The file "%s" could not be opened.', $path));
    }
}

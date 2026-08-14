<?php

namespace Shetabit\Chunky\Exceptions;

class FileAccessException extends ChunkyException
{
    public static function forPath(string $path) : self
    {
        return new self(sprintf('The file "%s" could not be opened.', $path));
    }
}

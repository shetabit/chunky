<?php

namespace Shetabit\Chunky\Exceptions;

class FileNotFoundException extends ChunkyException
{
    public static function forPath(string $path) : self
    {
        return new self(sprintf('The file "%s" does not exist.', $path));
    }
}

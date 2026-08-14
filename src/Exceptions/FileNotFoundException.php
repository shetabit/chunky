<?php

namespace Shetabit\Chunky\Exceptions;

/**
 * A file that has to be there to go on is not there.
 */
class FileNotFoundException extends ChunkyException
{
    public static function forPath(string $path) : self
    {
        return new self(sprintf('The file "%s" does not exist.', $path));
    }
}

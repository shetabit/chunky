<?php

namespace Shetabit\Chunky\Exceptions;

use RuntimeException;

/**
 * The exception every exception of this package extends.
 *
 * Catching it catches everything the package throws on its own.
 */
class ChunkyException extends RuntimeException
{
}

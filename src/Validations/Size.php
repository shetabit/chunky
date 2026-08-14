<?php

namespace Shetabit\Chunky\Validations;

use Shetabit\Chunky\Contracts\FileInterface;
use Shetabit\Chunky\Contracts\ValidationInterface;
use Shetabit\Chunky\Exceptions\ValidationFailedException;

/**
 * @example new Size('5M')          nothing over five megabytes
 * @example new Size('5M', '1K')    and nothing under a kilobyte either
 */
class Size implements ValidationInterface
{
    /**
     * By the power of 1024 each one stands for.
     */
    protected const array UNITS = ['b', 'k', 'm', 'g', 't', 'p'];

    protected int $maximum;

    protected int $minimum;

    public function __construct(int|string $maximum, int|string $minimum = 0)
    {
        $this->maximum = self::toBytes($maximum);
        $this->minimum = self::toBytes($minimum);
    }

    public function validate(FileInterface $file) : void
    {
        $chunk = $file->getSize();

        // A chunk without an announced size is all there is of its file, as far as
        // anyone here knows.
        $size = is_numeric($file->size) ? (int) $file->size : $chunk;

        if ($size > $this->maximum) {
            throw new ValidationFailedException(
                sprintf('The file is too large: %d bytes, and %d is the most that is taken.', $size, $this->maximum),
                $file
            );
        }

        // A client that announced a small file and kept sending is caught here.
        if ($chunk > $this->maximum) {
            throw new ValidationFailedException(
                sprintf('The chunk is too large: %d bytes, and %d is the most that is taken.', $chunk, $this->maximum),
                $file
            );
        }

        if ($size < $this->minimum) {
            throw new ValidationFailedException(
                sprintf('The file is too small: %d bytes, and %d is the least that is taken.', $size, $this->minimum),
                $file
            );
        }
    }

    public function getMaximum() : int
    {
        return $this->maximum;
    }

    public function getMinimum() : int
    {
        return $this->minimum;
    }

    /**
     * `5M`, `512 KB`, `1.5g`, `2048`.
     */
    public static function toBytes(int|string $size) : int
    {
        if (is_int($size)) {
            return $size;
        }

        if (preg_match('/^\s*([\d.]+)\s*([bkmgtp])b?\s*$/i', $size, $matches) !== 1) {
            return (int) $size;
        }

        $power = array_search(strtolower($matches[2]), self::UNITS, true);

        return (int) ((float) $matches[1] * 1024 ** (is_int($power) ? $power : 0));
    }
}

<?php

namespace Shetabit\Chunky\Tests\Fixtures;

use Shetabit\Chunky\Contracts\FileInterface;
use Shetabit\Chunky\Contracts\ValidationInterface;
use Shetabit\Chunky\Exceptions\ValidationFailedException;

final readonly class RefusingValidation implements ValidationInterface
{
    public function __construct(private string $reason = 'No file is taken here.')
    {
    }

    public function validate(FileInterface $file) : void
    {
        throw new ValidationFailedException($this->reason, $file);
    }
}

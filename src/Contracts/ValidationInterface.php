<?php

namespace Shetabit\Chunky\Contracts;

use Shetabit\Chunky\Exceptions\ValidationFailedException;

interface ValidationInterface
{
    /**
     * Runs against a single chunk before anything of it is written, so a file can be
     * turned away at its first chunk instead of after its last.
     *
     * @throws ValidationFailedException
     */
    public function validate(FileInterface $file) : void;
}

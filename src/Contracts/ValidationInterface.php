<?php

namespace Shetabit\Chunky\Contracts;

use Shetabit\Chunky\Exceptions\ValidationFailedException;

interface ValidationInterface
{
    /**
     * Check the given file, and say what is wrong with it by throwing.
     *
     * A validation runs against a single chunk, before anything of it is written. A
     * chunk knows the size, the name and the content type of the whole file it
     * belongs to, so a file can be turned away at its first chunk instead of after
     * the last one.
     *
     * @throws ValidationFailedException
     */
    public function validate(FileInterface $file) : void;
}

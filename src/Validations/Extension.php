<?php

namespace Shetabit\Chunky\Validations;

use Shetabit\Chunky\Contracts\FileInterface;
use Shetabit\Chunky\Contracts\ValidationInterface;
use Shetabit\Chunky\Exceptions\ValidationFailedException;

/**
 * Says what a file is allowed to be called, not what it is allowed to hold —
 * `MimeType` is the one that looks inside.
 *
 * @example new Extension(['jpg', 'jpeg', 'png', 'gif'])
 */
class Extension implements ValidationInterface
{
    /**
     * @var list<string>
     */
    protected array $allowed;

    /**
     * @param list<string> $allowed
     */
    public function __construct(array $allowed)
    {
        $this->allowed = array_map(
            static fn (string $extension): string => strtolower(ltrim(trim($extension), '.')),
            $allowed
        );
    }

    public function validate(FileInterface $file) : void
    {
        $name = is_string($file->name) ? $file->name : '';
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($extension === '') {
            throw new ValidationFailedException(
                sprintf('The file has no extension, and one of %s is taken.', $this->allowedAsText()),
                $file
            );
        }

        if (! in_array($extension, $this->allowed, true)) {
            throw new ValidationFailedException(
                sprintf('Files of the type "%s" are not taken, only %s.', $extension, $this->allowedAsText()),
                $file
            );
        }
    }

    /**
     * @return list<string>
     */
    public function getAllowed() : array
    {
        return $this->allowed;
    }

    protected function allowedAsText() : string
    {
        return $this->allowed === [] ? 'nothing' : implode(', ', $this->allowed);
    }
}

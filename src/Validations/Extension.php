<?php

namespace Shetabit\Chunky\Validations;

use Shetabit\Chunky\Contracts\FileInterface;
use Shetabit\Chunky\Contracts\ValidationInterface;
use Shetabit\Chunky\Exceptions\ValidationFailedException;

/**
 * Turns away files whose name does not end in one of the given extensions.
 *
 * The name of a file is whatever the client called it, so this says what a file is
 * allowed to be called — not what it is allowed to hold. `MimeType` looks inside.
 *
 * @example new Extension(['jpg', 'jpeg', 'png', 'gif'])
 */
class Extension implements ValidationInterface
{
    /**
     * The extensions that are taken, lower case and without their dot.
     *
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
     * The extensions that are taken.
     *
     * @return list<string>
     */
    public function getAllowed() : array
    {
        return $this->allowed;
    }

    /**
     * The extensions that are taken, as a sentence.
     */
    protected function allowedAsText() : string
    {
        return $this->allowed === [] ? 'nothing' : implode(', ', $this->allowed);
    }
}

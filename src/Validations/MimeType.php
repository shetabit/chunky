<?php

namespace Shetabit\Chunky\Validations;

use finfo;
use Shetabit\Chunky\Contracts\FileInterface;
use Shetabit\Chunky\Contracts\ValidationInterface;
use Shetabit\Chunky\Exceptions\ValidationFailedException;

/**
 * Turns away files whose content type is not one of the given ones.
 *
 * The content type of an upload is what the client says it is, so it is checked
 * against the bytes that arrived as well — but only for a chunk that holds the
 * beginning of its file, because that is where the bytes that give a file away sit. A
 * chunk out of the middle of a video looks like nothing in particular, and there is
 * no way around that: it is what a chunked upload is.
 *
 * @example new MimeType(['image/jpeg', 'image/png'])
 * @example new MimeType(['image/jpeg'], detect: false)    take the client's word for it
 */
class MimeType implements ValidationInterface
{
    /**
     * How many bytes of a file are enough to tell what it is.
     */
    protected const int SAMPLE_SIZE = 4096;

    /**
     * The content types that are taken, lower case.
     *
     * @var list<string>
     */
    protected array $allowed;

    /**
     * @param list<string> $allowed
     * @param bool         $detect whether the content of a file is looked into, and
     *                             has to agree with what the client called it
     */
    public function __construct(array $allowed, protected bool $detect = true)
    {
        $this->allowed = array_map(
            static fn (string $mime): string => strtolower(trim($mime)),
            $allowed
        );
    }

    public function validate(FileInterface $file) : void
    {
        $declared = is_string($file->mime) ? strtolower(trim($file->mime)) : '';

        if ($declared === '' || ! in_array($declared, $this->allowed, true)) {
            throw new ValidationFailedException(
                sprintf(
                    'Files of the type "%s" are not taken, only %s.',
                    $declared === '' ? 'unknown' : $declared,
                    implode(', ', $this->allowed) === '' ? 'nothing' : implode(', ', $this->allowed)
                ),
                $file
            );
        }

        if (! $this->detect || ! $this->holdsTheBeginningOfItsFile($file)) {
            return;
        }

        $detected = $this->detect($file);

        if ($detected !== null && $detected !== $declared) {
            throw new ValidationFailedException(
                sprintf('The file says it is "%s", and it is "%s".', $declared, $detected),
                $file
            );
        }
    }

    /**
     * The content types that are taken.
     *
     * @return list<string>
     */
    public function getAllowed() : array
    {
        return $this->allowed;
    }

    /**
     * What the content of the given file says it is.
     */
    protected function detect(FileInterface $file) : string|null
    {
        $sample = $file->read(0, self::SAMPLE_SIZE);

        if ($sample === '') {
            return null;
        }

        $detected = new finfo(FILEINFO_MIME_TYPE)->buffer($sample);

        return $detected === false ? null : strtolower($detected);
    }

    /**
     * Whether the given chunk is the one the file starts with.
     */
    protected function holdsTheBeginningOfItsFile(FileInterface $file) : bool
    {
        if (! is_string($file->range) || $file->range === '') { // no range: a whole file
            return true;
        }

        return str_starts_with($file->range, '0-');
    }
}

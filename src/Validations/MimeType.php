<?php

namespace Shetabit\Chunky\Validations;

use finfo;
use Shetabit\Chunky\Contracts\FileInterface;
use Shetabit\Chunky\Contracts\ValidationInterface;
use Shetabit\Chunky\Exceptions\ValidationFailedException;

/**
 * The bytes that give a file away sit at its beginning, so only a chunk holding that
 * beginning is looked into. A chunk out of the middle of a video looks like nothing
 * in particular, and there is no way around that: it is what a chunked upload is.
 *
 * @example new MimeType(['image/jpeg', 'image/png'])
 * @example new MimeType(['image/jpeg'], detect: false)    take the client's word for it
 */
class MimeType implements ValidationInterface
{
    protected const int SAMPLE_SIZE = 4096;

    /**
     * @var list<string>
     */
    protected array $allowed;

    /**
     * @param list<string> $allowed
     * @param bool         $detect whether the content has to agree with the name
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
     * @return list<string>
     */
    public function getAllowed() : array
    {
        return $this->allowed;
    }

    protected function detect(FileInterface $file) : string|null
    {
        $sample = $file->read(0, self::SAMPLE_SIZE);

        if ($sample === '') {
            return null;
        }

        $detected = new finfo(FILEINFO_MIME_TYPE)->buffer($sample);

        return $detected === false ? null : strtolower($detected);
    }

    protected function holdsTheBeginningOfItsFile(FileInterface $file) : bool
    {
        if (! is_string($file->range) || $file->range === '') { // no range: a whole file
            return true;
        }

        return str_starts_with($file->range, '0-');
    }
}

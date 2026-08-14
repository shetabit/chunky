<?php

namespace Chunky\Example;

use Shetabit\Chunky\Classes\Collector;
use Shetabit\Chunky\Contracts\TempFileInterface;

/**
 * A collector that reads the two things a chunk can not say for itself out of the
 * request it arrived in: which bytes of the file it holds, and how big that file is.
 *
 * A base64 data uri and a json payload carry both in their meta section, but a chunk
 * that is sent as a plain upload has nowhere to put them: `$_FILES` holds a name, a
 * type and the size of the chunk itself, and that is all.
 *
 * So they travel next to the upload, as fields of their own, and this is what puts
 * them back together. Every uploader that cuts a file into chunks sends something of
 * the kind; only the names of the fields differ.
 *
 * The size matters as much as the range: it is what a `Size` validation reads, and it
 * is what tells the difference between a chunk of a file and the whole of it. Without
 * it a client could send a file of any size, one small chunk at a time.
 */
final class ChunkedUploadCollector extends Collector
{
    public function __construct(
        string|null $inputName = null,
        private readonly string $rangeInputName = 'range',
        private readonly string $sizeInputName = 'size',
    ) {
        parent::__construct($inputName);
    }

    /**
     * @return list<TempFileInterface>
     */
    public function collect() : array
    {
        $files = parent::collect();

        $range = $this->field($this->rangeInputName);
        $size = $this->field($this->sizeInputName);

        foreach ($files as $file) {
            // A chunk that brought one of them along keeps its own.
            if ($range !== null && (! is_string($file->range) || $file->range === '')) {
                $file->range = $range;
            }

            if ($size !== null && is_numeric($size)) {
                $file->size = (int) $size;
            }
        }

        return $files;
    }

    /**
     * A field of the request, when it was sent and is a string.
     */
    private function field(string $name) : string|null
    {
        $value = $_POST[$name] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}

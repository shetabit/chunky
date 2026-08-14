<?php

namespace Chunky\Example;

use Shetabit\Chunky\Classes\Collector;
use Shetabit\Chunky\Contracts\TempFileInterface;

/**
 * A collector that reads the range of a chunk out of the request.
 *
 * A chunk carries the bytes of the whole file it holds in its `range` attribute, and
 * that is what tells `StreamIn` where to write it. A base64 data uri and a json
 * payload can carry that range themselves — in their meta section — but a chunk that
 * is sent as a plain upload has nowhere to put it: `$_FILES` holds a name, a type and
 * a size, and that is all.
 *
 * So the range travels next to the upload, as a field of its own, and this is what
 * puts the two together. Every uploader that cuts a file into chunks sends something
 * of the kind; only the name of the field differs.
 */
final class RangeAwareCollector extends Collector
{
    public function __construct(
        string|null $inputName = null,
        private readonly string $rangeInputName = 'range',
    ) {
        parent::__construct($inputName);
    }

    /**
     * @return list<TempFileInterface>
     */
    public function collect() : array
    {
        $files = parent::collect();
        $range = $_POST[$this->rangeInputName] ?? null;

        if (! is_string($range) || $range === '') {
            return $files;
        }

        foreach ($files as $file) {
            // A chunk that brought a range of its own keeps it.
            if (! is_string($file->range) || $file->range === '') {
                $file->range = $range;
            }
        }

        return $files;
    }
}

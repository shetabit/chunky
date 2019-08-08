<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\StreamInterface;

class StreamIn implements StreamInterface
{
    private $inputName;
    private $path;
    private $collector;
    private $files;

    const RANGE_DELIMITER = '-';

    function __construct($inputName, $path)
    {
        $this->inputName = $inputName;
        $this->path = $path;
        $this->collector = new Collector($inputName);
    }

    public function process()
    {
        $storedFiles = [];

        $files = $this->files = $this->collector->collect();

        foreach ($files as $file) {
            if (empty($file->name)) {
               continue;
            }

            $range = $this->getRange($file);

            $path = $this->path.DIRECTORY_SEPARATOR.$file->name;

            $file->saveAs($path, ...$range);

            $storedFile = [
                'path' => $path,
                'range' => $range,
                'file' => $file
            ];

            array_push($storedFiles, $storedFile);
        }

        return $storedFiles;
    }

    public function getRange($file)
    {
        $range = [0, $file->size];

        if (!empty($file->range)) {
            $t = explode(self::RANGE_DELIMITER, $file->range);

            $range = count($t) === 2 ? $t : $range;
        }

        return $range;
    }
}

<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Adapters\JsonToTempFile;
use Shetabit\Chunky\Contracts\TempFileInterface;

trait CollectJsonFile
{
    public function collectJsonFiles()
    {
        $inputFiles = empty($_REQUEST[$this->getInputName()]) ? null : $_REQUEST[$this->getInputName()];
        $collectedFiles = [];

        if (empty($inputFiles)) { // there is no file
            // do nothing
        } else if (is_array($inputFiles)) { // there is an array of files
            foreach ($inputFiles as $json) {
                if ($this->hasJsonFormat($json)) {
                    array_push($collectedFiles, $this->generateJsonTempFile($json));
                }
            }
        } else { // there is a single file
            if ($this->hasJsonFormat($inputFiles)) {
                array_push($collectedFiles, $this->generateJsonTempFile($inputFiles));
            }
        }

        return $collectedFiles;
    }

    public function hasJsonFormat($jsonString)
    {
        @json_decode($jsonString);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public function generateJsonTempFile($json) : TempFileInterface
    {
        return (new JsonToTempFile($json))->toTempFile();
    }
}

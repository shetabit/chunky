<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Adapters\JsonToTempFile;
use Shetabit\Chunky\Contracts\TempFileInterface;

trait CollectJsonFile
{
    abstract public function getInputName() : string|null;

    /**
     * @return list<TempFileInterface>
     */
    public function collectJsonFiles() : array
    {
        $inputName = $this->getInputName();
        $inputFiles = $inputName === null ? null : ($_REQUEST[$inputName] ?? null);
        $collectedFiles = [];

        foreach (is_array($inputFiles) ? $inputFiles : [$inputFiles] as $json) {
            if ($this->hasJsonFormat($json)) {
                $collectedFiles[] = $this->generateJsonTempFile($json);
            }
        }

        return $collectedFiles;
    }

    /**
     * Anything that decodes into something other than an object with a `data` key is
     * left alone: a number, a string and `null` are valid json on their own.
     */
    public function hasJsonFormat(mixed $jsonString) : bool
    {
        if (! is_string($jsonString) || ! json_validate($jsonString)) {
            return false;
        }

        $decoded = json_decode($jsonString, true);

        return is_array($decoded) && isset($decoded['data']) && is_string($decoded['data']);
    }

    public function generateJsonTempFile(string $json) : TempFileInterface
    {
        return new JsonToTempFile($json)->toTempFile();
    }
}

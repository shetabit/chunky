<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Adapters\JsonToTempFile;
use Shetabit\Chunky\Contracts\TempFileInterface;

trait CollectJsonFile
{
    /**
     * The name of the input the files are read from.
     */
    abstract public function getInputName() : string|null;

    /**
     * Collect every json payload that was sent under the input's name.
     *
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
     * Determine whether the given value is a json payload holding a file.
     *
     * Anything that decodes into something other than an object with a `data` key is
     * left alone: a number, a string and `null` are valid json on their own, and an
     * object without a payload holds no file. Request input that is none of the
     * package's business used to be collected as a file and to fall apart later.
     */
    public function hasJsonFormat(mixed $jsonString) : bool
    {
        if (! is_string($jsonString) || ! json_validate($jsonString)) {
            return false;
        }

        $decoded = json_decode($jsonString, true);

        return is_array($decoded) && isset($decoded['data']) && is_string($decoded['data']);
    }

    /**
     * Turn a json payload into a temporary file.
     */
    public function generateJsonTempFile(string $json) : TempFileInterface
    {
        return new JsonToTempFile($json)->toTempFile();
    }
}

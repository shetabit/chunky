<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Adapters\Base64ToTempFile;
use Shetabit\Chunky\Contracts\TempFileInterface;

trait CollectBase64File
{
    /**
     * The name of the input the files are read from.
     */
    abstract public function getInputName() : string|null;

    /**
     * Collect every base64 data uri that was sent under the input's name.
     *
     * @return list<TempFileInterface>
     */
    public function collectBase64Files() : array
    {
        $inputName = $this->getInputName();
        $inputFiles = $inputName === null ? null : ($_REQUEST[$inputName] ?? null);
        $collectedFiles = [];

        foreach (is_array($inputFiles) ? $inputFiles : [$inputFiles] as $uri) {
            if ($this->hasBase64Format($uri)) {
                $collectedFiles[] = $this->generateBase64TempFile($uri);
            }
        }

        return $collectedFiles;
    }

    /**
     * Determine whether the given value is a base64 data uri.
     */
    public function hasBase64Format(mixed $base64String) : bool
    {
        return is_string($base64String) && preg_match(Base64ToTempFile::PATTERN, $base64String) === 1;
    }

    /**
     * Turn a base64 data uri into a temporary file.
     */
    public function generateBase64TempFile(string $uri) : TempFileInterface
    {
        return new Base64ToTempFile($uri)->toTempFile();
    }
}

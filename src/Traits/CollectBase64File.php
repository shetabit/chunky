<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Adapters\Base64ToTempFile;
use Shetabit\Chunky\Contracts\TempFileInterface;

trait CollectBase64File
{
    abstract public function getInputName() : string|null;

    /**
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

    public function hasBase64Format(mixed $base64String) : bool
    {
        return is_string($base64String) && preg_match(Base64ToTempFile::PATTERN, $base64String) === 1;
    }

    public function generateBase64TempFile(string $uri) : TempFileInterface
    {
        return new Base64ToTempFile($uri)->toTempFile();
    }
}

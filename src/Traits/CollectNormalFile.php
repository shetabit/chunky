<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Classes\TempFile;
use Shetabit\Chunky\Contracts\TempFileInterface;

trait CollectNormalFile
{
    abstract public function getInputName() : string|null;

    /**
     * @return list<TempFileInterface>
     */
    public function collectFiles() : array
    {
        $inputName = $this->getInputName();
        $inputFiles = $inputName === null ? null : ($_FILES[$inputName] ?? null);

        if (! is_array($inputFiles) || ! isset($inputFiles['tmp_name'])) {
            return [];
        }

        // $_FILES holds an array of attributes either way, whose values are arrays
        // themselves when several files were sent.
        $uploads = is_array($inputFiles['tmp_name'])
            ? array_keys($inputFiles['tmp_name'])
            : [null];

        $collectedFiles = [];

        foreach ($uploads as $index) {
            $error = $this->uploadAttribute($inputFiles, 'error', $index);

            if ((int) $error !== UPLOAD_ERR_OK) {
                continue;
            }

            $collectedFiles[] = $this->generateTempFile(
                (string) $this->uploadAttribute($inputFiles, 'tmp_name', $index),
                (string) $this->uploadAttribute($inputFiles, 'name', $index),
                (string) $this->uploadAttribute($inputFiles, 'type', $index),
                (int) $this->uploadAttribute($inputFiles, 'size', $index),
            );
        }

        return $collectedFiles;
    }

    public function generateTempFile(string $path, string $name, string $mime, int $size) : TempFileInterface
    {
        $tempFile = new TempFile($path);

        $tempFile->name = $name;
        $tempFile->mime = $mime;
        $tempFile->size = $size;

        return $tempFile;
    }

    /**
     * @param array<string, mixed> $upload
     */
    private function uploadAttribute(array $upload, string $attribute, int|string|null $index) : mixed
    {
        $value = $upload[$attribute] ?? null;

        if ($index === null) {
            return is_array($value) ? null : $value;
        }

        return is_array($value) ? ($value[$index] ?? null) : null;
    }
}

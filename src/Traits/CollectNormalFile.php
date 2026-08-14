<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Classes\TempFile;
use Shetabit\Chunky\Contracts\TempFileInterface;

trait CollectNormalFile
{
    /**
     * The name of the input the files are read from.
     */
    abstract public function getInputName() : string|null;

    /**
     * Collect every uploaded file that was sent under the input's name.
     *
     * @return list<TempFileInterface>
     */
    public function collectFiles() : array
    {
        $inputName = $this->getInputName();
        $inputFiles = $inputName === null ? null : ($_FILES[$inputName] ?? null);

        if (! is_array($inputFiles) || ! isset($inputFiles['tmp_name'])) {
            return [];
        }

        // A single upload and an array of uploads are both handed over as an array of
        // attributes, the values of which are arrays themselves for the latter.
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

    /**
     * Turn an uploaded file into a temporary file.
     */
    public function generateTempFile(string $path, string $name, string $mime, int $size) : TempFileInterface
    {
        $tempFile = new TempFile($path);

        $tempFile->name = $name;
        $tempFile->mime = $mime;
        $tempFile->size = $size;

        return $tempFile;
    }

    /**
     * Read a single attribute of an uploaded file out of `$_FILES`.
     *
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

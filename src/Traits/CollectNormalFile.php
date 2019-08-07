<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Classes\TempFile;
use Shetabit\Chunky\Contracts\TempFileInterface;

trait CollectNormalFile
{
    public function collectFiles()
    {
        $inputFiles = empty($_FILES[$this->getInputName()]) ? null : $_FILES[$this->getInputName()];
        $collectedFiles = [];

        if (empty($inputFiles)) { // there is no file
            // do nothing
        } else if (is_array($inputFiles['tmp_name'])) { // there is an array of files
            foreach ($inputFiles['tmp_name'] as $index => $tmpName) {
                if ($inputFiles['error'][$index] === UPLOAD_ERR_OK) {
                    $name = $inputFiles['name'][$index];
                    $type = $inputFiles['type'][$index];
                    $size = $inputFiles['size'][$index];

                    array_push($collectedFiles, $this->generateTempFile($name, $type, $size));
                }
            }
        } else { // there is a single file
            if ($inputFiles['error'] === UPLOAD_ERR_OK) {
                $name = $inputFiles['name'];
                $type = $inputFiles['type'];
                $size = $inputFiles['size'];

                array_push($collectedFiles, $this->generateTempFile($name, $type, $size));
            }
        }

        return $collectedFiles;
    }

    public function generateTempFile($name, $mime, $size) : TempFileInterface
    {
        $tempFile = new TempFile;

        $tempFile->name = $name;
        $tempFile->mime = $mime;
        $tempFile->size = $size;

        return $tempFile;
    }
}

<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\TempFileInterface;

class Uploader
{
    public $files = [];

    public function __construct($inputName)
    {
        $this->collect($inputName);
    }

    public function collect($inputName)
    {
        // Collect chunk info
        $this->collectFiles($inputName);

    }

    public function collectFiles($inputName)
    {
        if (!empty($_FILES[$inputName])) {
            if (is_array($_FILES[$inputName]['tmp_name'])) {
                foreach ($_FILES[$inputName]['tmp_name'] as $index => $tmpName) {
                    if ($_FILES[$inputName]['error'][$index] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    array_push($this->files, $this->generateTempUsingFile($_FILES[$inputName]['tmp_name'][$index]));
                }
            } else {
                if ($_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                    array_push($this->files, $this->generateTempUsingFile($_FILES[$inputName]['tmp_name']));
                }
            }
        }

        if (!empty($_REQUEST[$inputName])) {
            if (is_array($_REQUEST[$inputName])) {
                foreach ($_REQUEST[$inputName] as $json) {
                    if ($this->hasJsonFormat($json)) {
                        array_push($this->files, $this->generateTempUsingJson($json));
                    } else {
                        array_push($this->files, $this->generateTempUsingBase64($_REQUEST[$inputName]));
                    }
                }
            } else {
                if ($this->hasJsonFormat($_REQUEST[$inputName])) {
                    array_push($this->files, $this->generateTempUsingJson($_REQUEST[$inputName]));
                } else {
                    array_push($this->files, $this->generateTempUsingBase64($_REQUEST[$inputName]));
                }
            }
        }

    }

    public function hasJsonFormat($jsonString)
    {
        @json_decode($jsonString);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public function generateTempUsingFile($name) : TempFileInterface
    {
        return new TempFile($name);
    }

    public function generateTempUsingJson($json) : TempFileInterface
    {
        return (new JsonToTempFile($json))->toTempFile();
    }

    public function generateTempUsingBase64($uri) : TempFileInterface
    {
        return (new Base64ToTempFile($uri))->toTempFile();
    }
}

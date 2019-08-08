<?php

namespace Shetabit\Chunky\Traits;

use Shetabit\Chunky\Adapters\Base64ToTempFile;
use Shetabit\Chunky\Contracts\TempFileInterface;

trait CollectBase64File
{
    public function collectBase64Files()
    {
        $inputFiles = empty($_REQUEST[$this->getInputName()]) ? null : $_REQUEST[$this->getInputName()];
        $collectedFiles = [];

        if (empty($inputFiles)) { // there is no file
            // do nothing
        } else if (is_array($inputFiles)) { // there is an array of files
            foreach ($inputFiles as $json) {
                if ($this->hasBase64Format($json)) {
                    array_push($collectedFiles, $this->generateBase64TempFile($json));
                }
            }
        } else { // there is a single file
            if ($this->hasBase64Format($inputFiles)) {
                array_push($collectedFiles, $this->generateBase64TempFile($inputFiles));
            }
        }

        return $collectedFiles;
    }

    public function hasBase64Format($base64String)
    {
        $pattern = '/^data:((?:\w+\/(?:(?!;).)+)?)((?:;[\w\W]*?[^;])*),(.+)$/iu';

        return (bool) preg_match($pattern, $base64String);
    }

    public function generateBase64TempFile($uri) : TempFileInterface
    {
        return (new Base64ToTempFile($uri))->toTempFile();
    }
}

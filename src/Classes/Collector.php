<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Traits\CollectBase64File;
use Shetabit\Chunky\Traits\CollectJsonFile;
use Shetabit\Chunky\Traits\CollectNormalFile;

class Collector
{
    use CollectBase64File,
        CollectJsonFile,
        CollectNormalFile;

    protected $inputName;

    public function __construct($inputName = null)
    {
        $this->setInputName($inputName);
    }

    public function setInputName($name)
    {
        $this->inputName = $name;

        return $this;
    }

    public function getInputName()
    {
        return $this->inputName;
    }

    public function collect()
    {
        // collect files
        $files = $this->collectFiles();

        // collect data files
        $base64Files = $this->collectBase64Files();

        // collect json files
        $jsonFiles = $this->collectJsonFiles();

        return array_merge($files, $base64Files, $jsonFiles);
    }
}

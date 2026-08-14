<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\TempFileInterface;
use Shetabit\Chunky\Traits\CollectBase64File;
use Shetabit\Chunky\Traits\CollectJsonFile;
use Shetabit\Chunky\Traits\CollectNormalFile;

class Collector
{
    use CollectBase64File;
    use CollectJsonFile;
    use CollectNormalFile;

    /**
     * The name of the html input the files are read from.
     */
    protected string|null $inputName = null;

    /**
     * Collector constructor.
     */
    public function __construct(string|null $inputName = null)
    {
        $this->setInputName($inputName);
    }

    /**
     * Set the name of the html input the files are read from.
     */
    public function setInputName(string|null $name) : static
    {
        $this->inputName = $name;

        return $this;
    }

    /**
     * Retrieve the name of the html input the files are read from.
     */
    public function getInputName() : string|null
    {
        return $this->inputName;
    }

    /**
     * Collect every file that was sent under the input's name, in every format the
     * package reads: uploaded files, base64 data uris and json payloads.
     *
     * @return list<TempFileInterface>
     */
    public function collect() : array
    {
        return array_merge(
            $this->collectFiles(),      // uploaded files
            $this->collectBase64Files(), // base64 data uris
            $this->collectJsonFiles(),   // json payloads
        );
    }
}

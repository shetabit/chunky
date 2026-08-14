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

    protected string|null $inputName = null;

    public function __construct(string|null $inputName = null)
    {
        $this->setInputName($inputName);
    }

    public function setInputName(string|null $name) : static
    {
        $this->inputName = $name;

        return $this;
    }

    public function getInputName() : string|null
    {
        return $this->inputName;
    }

    /**
     * Every file sent under the input's name, in all three formats.
     *
     * @return list<TempFileInterface>
     */
    public function collect() : array
    {
        return array_merge(
            $this->collectFiles(),
            $this->collectBase64Files(),
            $this->collectJsonFiles(),
        );
    }
}

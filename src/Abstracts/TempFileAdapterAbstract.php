<?php

namespace Shetabit\Chunky\Abstracts;

use Shetabit\Chunky\Classes\TempFile;
use Shetabit\Chunky\Contracts\TempFileAdapterInterface;
use Shetabit\Chunky\Contracts\TempFileInterface;

abstract class TempFileAdapterAbstract implements TempFileAdapterInterface
{
    protected $encodedData;

    public function __construct($encodedData = null)
    {
        $this->setEncodedData($encodedData);
    }

    public function setEncodedData($data)
    {
        $this->encodedData = $data;
    }

    public function getEncodedData()
    {
        return $this->encodedData;
    }

    abstract public function decodeData();

    public function toTempFile(): TempFileInterface
    {
        $tempFile = new TempFile;

        $decoded = $this->decodeData();

        foreach ($decoded as $k => $v) {
            if($k !== 'data') {
                $tempFile->$k = $v;
            }
        }

        $tempFile->create($decoded['data']);

        return $tempFile;
    }
}

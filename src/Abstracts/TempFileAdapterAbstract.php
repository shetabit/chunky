<?php

namespace Shetabit\Chunky\Abstracts;

use Shetabit\Chunky\Classes\TempFile;
use Shetabit\Chunky\Contracts\TempFileAdapterInterface;
use Shetabit\Chunky\Contracts\TempFileInterface;

abstract class TempFileAdapterAbstract implements TempFileAdapterInterface
{
    protected string|null $encodedData = null;

    public function __construct(string|null $encodedData = null)
    {
        $this->setEncodedData($encodedData);
    }

    public function setEncodedData(string|null $data) : static
    {
        $this->encodedData = $data;

        return $this;
    }

    public function getEncodedData() : string|null
    {
        return $this->encodedData;
    }

    /**
     * @return array{data: string, ...<string, mixed>}
     */
    abstract public function decodeData() : array;

    public function toTempFile() : TempFileInterface
    {
        $decoded = $this->decodeData();

        $tempFile = new TempFile();

        foreach ($decoded as $key => $value) {
            if ($key !== 'data') {
                $tempFile->{(string) $key} = $value;
            }
        }

        $tempFile->create($decoded['data']);

        return $tempFile;
    }
}

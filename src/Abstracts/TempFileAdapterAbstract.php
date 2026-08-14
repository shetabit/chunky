<?php

namespace Shetabit\Chunky\Abstracts;

use Shetabit\Chunky\Classes\TempFile;
use Shetabit\Chunky\Contracts\TempFileAdapterInterface;
use Shetabit\Chunky\Contracts\TempFileInterface;

abstract class TempFileAdapterAbstract implements TempFileAdapterInterface
{
    /**
     * The encoded payload the adapter reads.
     */
    protected string|null $encodedData = null;

    /**
     * TempFileAdapterAbstract constructor.
     */
    public function __construct(string|null $encodedData = null)
    {
        $this->setEncodedData($encodedData);
    }

    /**
     * Set the encoded payload the adapter reads.
     */
    public function setEncodedData(string|null $data) : static
    {
        $this->encodedData = $data;

        return $this;
    }

    /**
     * Retrieve the encoded payload the adapter reads.
     */
    public function getEncodedData() : string|null
    {
        return $this->encodedData;
    }

    /**
     * Decode the payload into the content of the file and its attributes.
     *
     * @return array{data: string, ...<string, mixed>}
     */
    abstract public function decodeData() : array;

    /**
     * Write the decoded payload into a temporary file.
     */
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

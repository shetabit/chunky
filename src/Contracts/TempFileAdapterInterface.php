<?php

namespace Shetabit\Chunky\Contracts;

interface TempFileAdapterInterface
{
    public function setEncodedData(string|null $data) : static;

    public function getEncodedData() : string|null;

    /**
     * The `data` key holds the content of the file, every other key becomes an
     * attribute of it.
     *
     * @return array{data: string, ...<string, mixed>}
     */
    public function decodeData() : array;

    public function toTempFile() : TempFileInterface;
}

<?php

namespace Shetabit\Chunky\Contracts;

interface TempFileAdapterInterface
{
    public function setEncodedData($data);

    public function getEncodedData();

    public function decodeData();

    public function toTempFile() : TempFileInterface;
}

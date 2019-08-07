<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\TempFileAdapterInterface;
use Shetabit\Chunky\Contracts\TempFileInterface;

class JsonToTempFile implements TempFileAdapterInterface
{
    protected $json;

    public function __construct($json = null)
    {
        $this->setJson($json);
    }

    public function setJson($json)
    {
        $this->json = $json;
    }

    public function getJson()
    {
        return $this->json;
    }

    public function getInfo($json)
    {
        $jsonObj = json_decode($json, false);

        return ($jsonObj->mime && $jsonObj->data) ? ['mime' => $jsonObj->mime, 'data'=> $jsonObj->data] : null;
    }

    public function toTempFile(): TempFileInterface
    {
        $tempFile = new TempFile;

        $base64EncodedData = $this->getInfo($this->getJson())['data'];

        $tempFile->write(base64_decode($base64EncodedData));

        return $tempFile;
    }
}

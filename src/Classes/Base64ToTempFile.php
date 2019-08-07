<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\TempFileAdapterInterface;
use Shetabit\Chunky\Contracts\TempFileInterface;

class Base64ToTempFile implements TempFileAdapterInterface
{
    protected $uri;

    public function __construct($uri = null)
    {
        $this->setUri($uri);
    }

    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getInfo()
    {
        $foundFlag = (bool) preg_match($this->getPattern(), $this->getUri(), $matches);

        return $foundFlag ? ['mime' => $matches[1], 'data' => $matches[2]] : null;
    }

    public function toTempFile(): TempFileInterface
    {
        $tempFile = new TempFile;

        $base64EncodedData = $this->getInfo()['data'];

        $tempFile->write(base64_decode($base64EncodedData));

        return $tempFile;
    }

    protected function getPattern()
    {
        return '/^data:(.*?);base64,(.*)/iu';
    }
}

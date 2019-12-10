<?php

namespace Shetabit\Chunky;

class InputStream
{
    protected $request;

    protected $decoder;

    protected $data;

    public function setDecoder(callable $decoder)
    {
        $this->decoder = $decoder;
    }

    protected function decode()
    {
        $decoder = $this->decoder;

        return empty($decoder) ? $this->data : $decoder($this->data);
    }

    public function data($data)
    {
        $this->data = $data;
    }

    public function save($path)
    {
        
    }
}

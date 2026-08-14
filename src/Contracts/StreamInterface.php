<?php

namespace Shetabit\Chunky\Contracts;

interface StreamInterface
{
    /**
     * @return mixed an incoming stream returns the chunks it stored, an outgoing one
     *               writes to the output and returns nothing
     */
    public function process();
}

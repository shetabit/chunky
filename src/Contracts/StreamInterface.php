<?php

namespace Shetabit\Chunky\Contracts;

interface StreamInterface
{
    /**
     * Run the stream.
     *
     * @return mixed what the stream produced, which is up to the implementation: an
     *               incoming stream returns the chunks it stored, an outgoing one
     *               writes to the output and returns nothing
     */
    public function process();
}

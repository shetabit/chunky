<?php

namespace Shetabit\Chunky\Contracts;

interface TempFileInterface extends FileInterface
{
    public function saveAs($path, $offset = null, $length = null);
}

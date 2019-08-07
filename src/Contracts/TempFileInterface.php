<?php

namespace Shetabit\Chunky\Contracts;

interface TempFileInterface extends FileInterface
{
    public function saveAs($path, $offset = 0, $length = null);
}

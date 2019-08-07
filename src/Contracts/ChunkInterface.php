<?php

namespace Shetabit\Chunky\Contracts;

interface ChunkInterface
{
    public function getChunkRange();

    public function getName();

    public function getExtension();

    public function getSize();

    public function getData();
}

<?php

namespace Shetabit\Chunky\Contracts;

interface TempFileAdapterInterface
{
    /**
     * Set the encoded payload the adapter reads.
     */
    public function setEncodedData(string|null $data) : static;

    /**
     * Retrieve the encoded payload the adapter reads.
     */
    public function getEncodedData() : string|null;

    /**
     * Decode the payload into the content of the file and its attributes.
     *
     * The `data` key holds the decoded content of the file, every other key becomes
     * an attribute of the temporary file.
     *
     * @return array{data: string, ...<string, mixed>}
     */
    public function decodeData() : array;

    /**
     * Write the decoded payload into a temporary file.
     */
    public function toTempFile() : TempFileInterface;
}

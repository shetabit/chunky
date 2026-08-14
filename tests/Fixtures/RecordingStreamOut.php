<?php

namespace Shetabit\Chunky\Tests\Fixtures;

use Shetabit\Chunky\Classes\StreamOut;

final class RecordingStreamOut extends StreamOut
{
    /**
     * The headers that were sent, in the order they were sent in.
     *
     * @var list<string>
     */
    public array $headers = [];

    public int|null $status = null;

    public function capture(string|null $rangeHeader = null) : string
    {
        ob_start();

        try {
            $this->process($rangeHeader);
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }

    public function header(string $name) : string|null
    {
        foreach (array_reverse($this->headers) as $header) {
            if (stripos($header, $name.':') === 0) {
                return trim(substr($header, strlen($name) + 1));
            }
        }

        return null;
    }

    protected function sendHeader(string $header, bool $replace = true, int $responseCode = 0) : void
    {
        $this->headers[] = $header;

        if ($responseCode !== 0) {
            $this->status = $responseCode;
        }
    }

    protected function afterBuffer() : void
    {
    }
}

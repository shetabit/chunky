<?php

namespace Shetabit\Chunky\Tests\Fixtures;

use Shetabit\Chunky\Classes\StreamOut;

/**
 * An outgoing stream that records its headers instead of sending them.
 *
 * `header()` does nothing under the command line, so this is what the tests read the
 * response of a stream out of.
 */
final class RecordingStreamOut extends StreamOut
{
    /**
     * The headers that were sent, in the order they were sent in.
     *
     * @var list<string>
     */
    public array $headers = [];

    /**
     * The status code of the response, `null` while it is the default one.
     */
    public int|null $status = null;

    /**
     * Run the stream and return what it wrote to the output.
     */
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

    /**
     * The value of the header of the given name, `null` when it was not sent.
     */
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

    /**
     * The stream is being written into an output buffer, there is nothing to hand
     * over to a client and nothing to throttle.
     */
    protected function afterBuffer() : void
    {
    }
}

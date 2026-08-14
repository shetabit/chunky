<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\StreamInterface;
use Shetabit\Chunky\Exceptions\FileAccessException;
use Shetabit\Chunky\Exceptions\FileNotFoundException;

class StreamOut implements StreamInterface
{
    public const int BUFFER_SIZE = 8192;

    public const string DEFAULT_MIME = 'application/octet-stream';

    protected string $name;

    /**
     * Separates the parts of a response holding more than one range.
     */
    protected string $boundary;

    protected int $size;

    protected string $mime;

    /**
     * @var resource
     */
    protected $file;

    /**
     * @param int $delay microseconds to wait after every buffer, to throttle the stream
     */
    public function __construct(
        protected string $path,
        protected int $delay = 0,
        protected int $bufferSize = self::BUFFER_SIZE,
    ) {
        if (! is_file($path)) {
            throw FileNotFoundException::forPath($path);
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw FileAccessException::forPath($path);
        }

        $this->file = $handle;
        $this->size = (int) filesize($path);
        $this->mime = mime_content_type($path) ?: self::DEFAULT_MIME;
        $this->boundary = md5($path);
        $this->name = basename($path);
        $this->bufferSize = max(1, $bufferSize);
    }

    /**
     * @param string|null $rangeHeader read from the request itself when it is not given
     */
    public function process(string|null $rangeHeader = null) : void
    {
        $ranges = $this->parseRanges($rangeHeader ?? $this->rangeHeaderOfRequest());

        if ($ranges === null) { // a range was asked for, and none of it can be served
            $this->sendStatus(416, 'Requested Range Not Satisfiable');
            $this->sendHeader(sprintf('Content-Range: bytes */%d', $this->size));

            return;
        }

        $this->sendHeader('Accept-Ranges: bytes');
        $this->sendHeader('Content-Transfer-Encoding: binary');
        $this->sendHeader(sprintf('Content-Disposition: attachment; filename="%s"', $this->name));

        match (count($ranges)) {
            0 => $this->pushAll(),
            1 => $this->pushSingle($ranges[0]),
            default => $this->pushMulti($ranges),
        };

        flush();
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getSize() : int
    {
        return $this->size;
    }

    public function getMime() : string
    {
        return $this->mime;
    }

    public function getBoundary() : string
    {
        return $this->boundary;
    }

    /**
     * An empty array means the whole file was asked for, `null` means a range was
     * asked for that can not be served.
     *
     * @return list<array{int, int}>|null
     */
    public function parseRanges(string|null $header) : array|null
    {
        $header = trim((string) $header);

        // Any unit other than bytes is served as a whole file, as the specification asks.
        if ($header === '' || stripos($header, 'bytes=') !== 0) {
            return [];
        }

        $ranges = [];

        foreach (explode(',', substr($header, 6)) as $range) {
            $parsed = $this->parseRange(trim($range));

            if ($parsed !== null) {
                $ranges[] = $parsed;
            }
        }

        return $ranges === [] ? null : $ranges;
    }

    public function __destruct()
    {
        if (is_resource($this->file)) {
            fclose($this->file);
        }
    }

    /**
     * @return array{int, int}|null
     */
    protected function parseRange(string $range) : array|null
    {
        if (! str_contains($range, '-')) {
            return null;
        }

        [$first, $last] = array_map(trim(...), explode('-', $range, 2));

        if ($first === '') { // `-500`, the last 500 bytes of the file
            if (! is_numeric($last) || (int) $last <= 0) {
                return null;
            }

            $start = max(0, $this->size - (int) $last);
            $end = $this->size - 1;
        } else { // `500-999` or `500-`, from the given byte on
            if (! is_numeric($first)) {
                return null;
            }

            $start = (int) $first;
            $end = is_numeric($last) ? min((int) $last, $this->size - 1) : $this->size - 1;
        }

        return $start > $end || $start >= $this->size ? null : [$start, $end];
    }

    protected function rangeHeaderOfRequest() : string|null
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($method !== 'GET' && $method !== 'HEAD') {
            return null;
        }

        $range = $_SERVER['HTTP_RANGE'] ?? null;

        return is_string($range) ? $range : null;
    }

    protected function pushAll() : void
    {
        $this->sendHeader('Content-Type: '.$this->mime);
        $this->sendHeader('Content-Length: '.$this->size);

        rewind($this->file);

        $this->readBuffer($this->size);
    }

    /**
     * @param array{int, int} $range
     */
    protected function pushSingle(array $range) : void
    {
        [$start, $end] = $range;
        $length = $end - $start + 1;

        $this->sendStatus(206, 'Partial Content');
        $this->sendHeader('Content-Type: '.$this->mime);
        $this->sendHeader('Content-Length: '.$length);
        $this->sendHeader(sprintf('Content-Range: bytes %d-%d/%d', $start, $end, $this->size));

        fseek($this->file, $start);

        $this->readBuffer($length);
    }

    /**
     * @param list<array{int, int}> $ranges
     */
    protected function pushMulti(array $ranges) : void
    {
        $partType = "Content-Type: {$this->mime}\r\n";
        $partRange = "Content-Range: bytes %d-%d/%d\r\n\r\n";
        $length = strlen("\r\n--{$this->boundary}--\r\n");

        foreach ($ranges as [$start, $end]) {
            $length += strlen("\r\n--{$this->boundary}\r\n");
            $length += strlen($partType);
            $length += strlen(sprintf($partRange, $start, $end, $this->size));
            $length += $end - $start + 1;
        }

        $this->sendStatus(206, 'Partial Content');
        $this->sendHeader('Content-Type: multipart/byteranges; boundary='.$this->boundary);
        $this->sendHeader('Content-Length: '.$length);

        foreach ($ranges as [$start, $end]) {
            echo "\r\n--{$this->boundary}\r\n";
            echo $partType;
            echo sprintf($partRange, $start, $end, $this->size);

            fseek($this->file, $start);

            $this->readBuffer($end - $start + 1);
        }

        echo "\r\n--{$this->boundary}--\r\n";
    }

    protected function readBuffer(int $bytes) : void
    {
        $bytesLeft = $bytes;

        while ($bytesLeft > 0 && ! feof($this->file)) {
            $buffer = fread($this->file, max(1, min($bytesLeft, $this->bufferSize)));

            if ($buffer === false || $buffer === '') {
                break;
            }

            $bytesLeft -= strlen($buffer);

            echo $buffer;

            $this->afterBuffer();
        }
    }

    protected function afterBuffer() : void
    {
        flush();

        if ($this->delay > 0) {
            usleep($this->delay);
        }
    }

    protected function sendStatus(int $code, string $text) : void
    {
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? null;
        $statusLine = sprintf('%s %d %s', is_string($protocol) ? $protocol : 'HTTP/1.1', $code, $text);

        $this->sendHeader($statusLine, true, $code);
    }

    protected function sendHeader(string $header, bool $replace = true, int $responseCode = 0) : void
    {
        if (headers_sent()) {
            return;
        }

        header($header, $replace, $responseCode);
    }
}

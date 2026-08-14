<?php

namespace Shetabit\Chunky\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Shetabit\Chunky\Classes\StreamOut;
use Shetabit\Chunky\Exceptions\FileAccessException;
use Shetabit\Chunky\Exceptions\FileNotFoundException;
use Shetabit\Chunky\Tests\Fixtures\RecordingStreamOut;
use Shetabit\Chunky\Tests\TestCase;

#[CoversClass(StreamOut::class)]
final class StreamOutTest extends TestCase
{
    public function testItReadsTheFileItIsPointedAt() : void
    {
        $stream = new StreamOut($this->writeFile('song.txt', 'abcdefghij'));

        $this->assertSame($this->path('song.txt'), $stream->getPath());
        $this->assertSame('song.txt', $stream->getName());
        $this->assertSame(10, $stream->getSize());
        $this->assertSame('text/plain', $stream->getMime());
        $this->assertSame(md5($this->path('song.txt')), $stream->getBoundary());
    }

    /**
     * A missing file used to send a `400` and to end the whole script, which left the
     * application no way of answering the request itself.
     */
    public function testItRefusesAFileThatDoesNotExist() : void
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('does not exist');

        new StreamOut($this->path('missing.mp3'));
    }

    public function testItRefusesADirectory() : void
    {
        $this->expectException(FileNotFoundException::class);

        new StreamOut($this->workspace);
    }

    public function testItRefusesAFileItCanNotRead() : void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('The permissions of a file mean nothing to the root user.');
        }

        $path = $this->writeFile('secret.txt', 'hidden');
        chmod($path, 0o000);

        try {
            $this->expectException(FileAccessException::class);
            $this->expectExceptionMessage('could not be opened');

            new StreamOut($path);
        } finally {
            chmod($path, 0o644);
        }
    }

    /**
     * @param list<array{int, int}>|null $expected
     */
    #[DataProvider('rangeHeaders')]
    public function testItReadsTheRangesOfARangeHeader(string|null $header, array|null $expected) : void
    {
        $stream = new StreamOut($this->writeFile('song.txt', str_repeat('a', 1000)));

        $this->assertSame($expected, $stream->parseRanges($header));
    }

    /**
     * @return array<string, array{string|null, list<array{int, int}>|null}>
     */
    public static function rangeHeaders() : array
    {
        return [
            'no header at all' => [null, []],
            'an empty header' => ['', []],
            'a unit that is not bytes' => ['items=0-10', []],
            'the first bytes' => ['bytes=0-99', [[0, 99]]],
            'a range in the middle' => ['bytes=500-599', [[500, 599]]],
            'an open range' => ['bytes=500-', [[500, 999]]],
            'the whole file' => ['bytes=0-', [[0, 999]]],
            'a single byte' => ['bytes=0-0', [[0, 0]]],
            'the last bytes' => ['bytes=-100', [[900, 999]]],
            'more of the last bytes than there are' => ['bytes=-5000', [[0, 999]]],
            'an end behind the file' => ['bytes=900-5000', [[900, 999]]],
            'several ranges' => ['bytes=0-99,500-599', [[0, 99], [500, 599]]],
            'several ranges with spaces' => ['bytes=0-99, 500-599', [[0, 99], [500, 599]]],
            'upper case' => ['BYTES=0-99', [[0, 99]]],
            'the ranges that can be served' => ['bytes=0-99,5000-6000', [[0, 99]]],
            'a start behind the file' => ['bytes=5000-6000', null],
            'a range that is turned around' => ['bytes=99-0', null],
            'a suffix of nothing' => ['bytes=-0', null],
            'a range that is no range' => ['bytes=nonsense', null],
            'a range without an end' => ['bytes=abc-def', null],
        ];
    }

    public function testEveryRangeOfAnEmptyFileIsUnsatisfiable() : void
    {
        $stream = new StreamOut($this->writeFile('empty.txt', ''));

        $this->assertNull($stream->parseRanges('bytes=0-'));
        $this->assertSame([], $stream->parseRanges(null));
    }

    public function testItWritesTheWholeFileWhenNoRangeWasAskedFor() : void
    {
        $stream = new RecordingStreamOut($this->writeFile('song.txt', 'abcdefghij'));

        $this->assertSame('abcdefghij', $stream->capture());

        $this->assertNull($stream->status);
        $this->assertSame('bytes', $stream->header('Accept-Ranges'));
        $this->assertSame('text/plain', $stream->header('Content-Type'));
        $this->assertSame('10', $stream->header('Content-Length'));
        $this->assertSame('binary', $stream->header('Content-Transfer-Encoding'));
        $this->assertSame('attachment; filename="song.txt"', $stream->header('Content-Disposition'));
    }

    public function testItWritesTheFileInBuffers() : void
    {
        $contents = random_bytes(100_000);

        $stream = new RecordingStreamOut($this->writeFile('big.bin', $contents), 0, 1024);

        $this->assertSame($contents, $stream->capture());
    }

    public function testItWritesAnEmptyFile() : void
    {
        $stream = new RecordingStreamOut($this->writeFile('empty.txt', ''));

        $this->assertSame('', $stream->capture());
        $this->assertSame('0', $stream->header('Content-Length'));
    }

    public function testItWritesTheRangeThatWasAskedFor() : void
    {
        $stream = new RecordingStreamOut($this->writeFile('song.txt', 'abcdefghij'));

        $this->assertSame('def', $stream->capture('bytes=3-5'));

        $this->assertSame(206, $stream->status);
        $this->assertSame('3', $stream->header('Content-Length'));
        $this->assertSame('bytes 3-5/10', $stream->header('Content-Range'));
        $this->assertSame('text/plain', $stream->header('Content-Type'));
    }

    /**
     * A single range used to be followed by the rest of the file, which is more than
     * the `Content-Length` of the response announces.
     */
    public function testItWritesNothingBehindTheRangeThatWasAskedFor() : void
    {
        $stream = new RecordingStreamOut($this->writeFile('song.txt', 'abcdefghij'));

        $body = $stream->capture('bytes=0-2');

        $this->assertSame('abc', $body);
        $this->assertSame((string) strlen($body), $stream->header('Content-Length'));
    }

    public function testItWritesTheLastBytesOfTheFile() : void
    {
        $stream = new RecordingStreamOut($this->writeFile('song.txt', 'abcdefghij'));

        $this->assertSame('hij', $stream->capture('bytes=-3'));
        $this->assertSame('bytes 7-9/10', $stream->header('Content-Range'));
    }

    public function testItWritesAnOpenRangeUntilTheEndOfTheFile() : void
    {
        $stream = new RecordingStreamOut($this->writeFile('song.txt', 'abcdefghij'));

        $this->assertSame('defghij', $stream->capture('bytes=3-'));
        $this->assertSame('bytes 3-9/10', $stream->header('Content-Range'));
    }

    public function testItWritesSeveralRangesAsAMultipartResponse() : void
    {
        $stream = new RecordingStreamOut($this->writeFile('song.txt', 'abcdefghij'));

        $body = $stream->capture('bytes=0-2,7-9');
        $boundary = $stream->getBoundary();

        $this->assertSame(206, $stream->status);
        $this->assertSame('multipart/byteranges; boundary='.$boundary, $stream->header('Content-Type'));
        $this->assertSame((string) strlen($body), $stream->header('Content-Length'));

        $this->assertSame(
            "\r\n--{$boundary}\r\n"
            ."Content-Type: text/plain\r\n"
            ."Content-Range: bytes 0-2/10\r\n\r\n"
            .'abc'
            ."\r\n--{$boundary}\r\n"
            ."Content-Type: text/plain\r\n"
            ."Content-Range: bytes 7-9/10\r\n\r\n"
            .'hij'
            ."\r\n--{$boundary}--\r\n",
            $body
        );
    }

    /**
     * An unsatisfiable range used to end the whole script instead of answering it.
     */
    public function testItAnswersARangeItCanNotServeWithA416() : void
    {
        $stream = new RecordingStreamOut($this->writeFile('song.txt', 'abcdefghij'));

        $this->assertSame('', $stream->capture('bytes=100-200'));

        $this->assertSame(416, $stream->status);
        $this->assertSame('bytes */10', $stream->header('Content-Range'));
        $this->assertNull($stream->header('Content-Length'));
    }

    public function testItReadsTheRangeOfTheRequestWhenItIsNotGivenOne() : void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_RANGE'] = 'bytes=3-5';

        $stream = new RecordingStreamOut($this->writeFile('song.txt', 'abcdefghij'));

        $this->assertSame('def', $stream->capture());
    }

    public function testItIgnoresTheRangeOfARequestThatIsNoDownload() : void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_RANGE'] = 'bytes=3-5';

        $stream = new RecordingStreamOut($this->writeFile('song.txt', 'abcdefghij'));

        $this->assertSame('abcdefghij', $stream->capture());
    }

    public function testItSendsTheStatusLineOfTheProtocolOfTheRequest() : void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/2';

        $stream = new RecordingStreamOut($this->writeFile('song.txt', 'abcdefghij'));
        $stream->capture('bytes=0-2');

        $this->assertContains('HTTP/2 206 Partial Content', $stream->headers);
    }

    /**
     * The stream itself, with nothing of it replaced: `header()` does nothing under
     * the command line, the body is all there is to look at.
     */
    public function testItSendsItsHeadersAndItsBodyOnItsOwn() : void
    {
        $stream = new StreamOut($this->writeFile('song.txt', 'abcdefghij'), 1, 4);

        ob_start();

        try {
            $stream->process('bytes=0-4');
        } finally {
            $body = (string) ob_get_clean();
        }

        $this->assertSame('abcde', $body);
    }

    public function testItClosesTheFileWhenItIsCollected() : void
    {
        $stream = new StreamOut($this->writeFile('song.txt', 'abcdefghij'));

        $before = count(get_resources('stream'));

        unset($stream);

        $this->assertCount($before - 1, get_resources('stream'));
    }
}

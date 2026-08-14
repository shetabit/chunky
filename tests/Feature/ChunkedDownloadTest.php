<?php

namespace Shetabit\Chunky\Tests\Feature;

use Shetabit\Chunky\Classes\StreamIn;
use Shetabit\Chunky\Tests\Fixtures\RecordingStreamOut;
use Shetabit\Chunky\Tests\TestCase;

final class ChunkedDownloadTest extends TestCase
{
    public function testAFileThatIsDownloadedRangeByRangeArrivesInOnePiece() : void
    {
        $contents = random_bytes(10_000);
        $path = $this->writeFile('holiday.mp4', $contents);

        $downloaded = '';

        for ($start = 0; $start < strlen($contents); $start += 2048) {
            $end = min($start + 2047, strlen($contents) - 1);

            $stream = new RecordingStreamOut($path);
            $body = $stream->capture(sprintf('bytes=%d-%d', $start, $end));

            $this->assertSame(206, $stream->status);
            $this->assertSame(sprintf('bytes %d-%d/10000', $start, $end), $stream->header('Content-Range'));
            $this->assertSame((string) strlen($body), $stream->header('Content-Length'));

            $downloaded .= $body;
        }

        $this->assertSame($contents, $downloaded);
    }

    public function testADownloadIsResumedWhereItWasInterrupted() : void
    {
        $contents = random_bytes(10_000);
        $path = $this->writeFile('holiday.mp4', $contents);

        // The client took the first 4000 bytes and lost its connection.
        $downloaded = substr($contents, 0, 4000);

        // It asks for the rest of the file, starting where it stopped.
        $stream = new RecordingStreamOut($path);
        $downloaded .= $stream->capture(sprintf('bytes=%d-', strlen($downloaded)));

        $this->assertSame('bytes 4000-9999/10000', $stream->header('Content-Range'));
        $this->assertSame($contents, $downloaded);
    }

    public function testSeveralRangesAreDownloadedInOneRequest() : void
    {
        $contents = random_bytes(10_000);
        $path = $this->writeFile('holiday.mp4', $contents);

        $stream = new RecordingStreamOut($path);
        $body = $stream->capture('bytes=0-999,5000-5999,-1000');

        $this->assertStringContainsString(substr($contents, 0, 1000), $body);
        $this->assertStringContainsString(substr($contents, 5000, 1000), $body);
        $this->assertStringContainsString(substr($contents, -1000), $body);

        $this->assertSame((string) strlen($body), $stream->header('Content-Length'));
        $this->assertStringStartsWith('multipart/byteranges;', (string) $stream->header('Content-Type'));
    }

    public function testAFileThatWasUploadedInChunksIsDownloadedInRanges() : void
    {
        $contents = random_bytes(8192);
        $chunks = str_split($contents, 2048);

        foreach ($chunks as $index => $chunk) {
            $_REQUEST['media'] = $this->dataUri($chunk, 'video/mp4', [
                'name' => 'holiday.mp4',
                'range' => sprintf('%d-%d', $index * 2048, ($index + 1) * 2048 - 1),
                'size' => strlen($contents),
            ]);

            new StreamIn('media', $this->workspace)->process();
        }

        $downloaded = '';

        for ($start = 0; $start < strlen($contents); $start += 3000) {
            $stream = new RecordingStreamOut($this->path('holiday.mp4'));

            $downloaded .= $stream->capture(sprintf('bytes=%d-%d', $start, $start + 2999));
        }

        $this->assertSame($contents, $downloaded);
    }

    public function testAClientThatAsksForMoreThanThereIsGetsA416() : void
    {
        $stream = new RecordingStreamOut($this->writeFile('holiday.mp4', random_bytes(100)));

        $this->assertSame('', $stream->capture('bytes=1000-2000'));
        $this->assertSame(416, $stream->status);
        $this->assertSame('bytes */100', $stream->header('Content-Range'));
    }

    public function testAClientThatAsksForNoRangeGetsTheWholeFile() : void
    {
        $contents = random_bytes(10_000);

        $stream = new RecordingStreamOut($this->writeFile('holiday.mp4', $contents));

        $this->assertSame($contents, $stream->capture());
        $this->assertSame('10000', $stream->header('Content-Length'));
        $this->assertSame('bytes', $stream->header('Accept-Ranges'));
    }
}

<?php

namespace Shetabit\Chunky\Tests\Feature;

use Shetabit\Chunky\Classes\ChunkedUploadCollector;
use Shetabit\Chunky\Classes\StreamIn;
use Shetabit\Chunky\Contracts\ValidationInterface;
use Shetabit\Chunky\Exceptions\ValidationFailedException;
use Shetabit\Chunky\Tests\TestCase;
use Shetabit\Chunky\Validations\Size;

final class MultiFileChunkedUploadTest extends TestCase
{
    private const int CHUNK_SIZE = 1024;

    public function testTwoFilesAreUploadedSideBySideChunkForChunk() : void
    {
        $first = random_bytes(self::CHUNK_SIZE * 3);
        $second = random_bytes(self::CHUNK_SIZE * 3);

        $chunksOfFirst = str_split($first, self::CHUNK_SIZE);
        $chunksOfSecond = str_split($second, self::CHUNK_SIZE);

        foreach ($chunksOfFirst as $index => $chunk) {
            $this->sendChunks([
                ['holiday.mp4', $chunk, $index, strlen($first)],
                ['song.mp3', $chunksOfSecond[$index], $index, strlen($second)],
            ]);
        }

        $this->assertSame($first, file_get_contents($this->path('holiday.mp4')));
        $this->assertSame($second, file_get_contents($this->path('song.mp3')));
    }

    public function testTheChunksOfTwoFilesLandInTheirOwnFileWhateverOrderTheyArriveIn() : void
    {
        $first = random_bytes(self::CHUNK_SIZE * 2);
        $second = random_bytes(self::CHUNK_SIZE * 2);

        $chunksOfFirst = str_split($first, self::CHUNK_SIZE);
        $chunksOfSecond = str_split($second, self::CHUNK_SIZE);

        // the tail of both files first, then the head of both
        $this->sendChunks([
            ['holiday.mp4', $chunksOfFirst[1], 1, strlen($first)],
            ['song.mp3', $chunksOfSecond[1], 1, strlen($second)],
        ]);

        $this->sendChunks([
            ['song.mp3', $chunksOfSecond[0], 0, strlen($second)],
            ['holiday.mp4', $chunksOfFirst[0], 0, strlen($first)],
        ]);

        $this->assertSame($first, file_get_contents($this->path('holiday.mp4')));
        $this->assertSame($second, file_get_contents($this->path('song.mp3')));
    }

    public function testEachFileIsMeasuredByItsOwnAnnouncedSize() : void
    {
        $this->expectException(ValidationFailedException::class);

        try {
            // the first one fits, the second one does not
            $this->sendChunks([
                ['small.bin', 'aaaa', 0, 4],
                ['huge.bin', 'bbbb', 0, 50_000],
            ], [new Size(1024)]);
        } finally {
            // nothing is written when a single chunk of the request is turned away
            $this->assertFileDoesNotExist($this->path('small.bin'));
            $this->assertFileDoesNotExist($this->path('huge.bin'));
        }
    }

    public function testTheStreamReportsWhereEachFileStands() : void
    {
        $stored = $this->sendChunks([
            ['first.bin', 'AAAA', 0, 8],
            ['second.bin', 'BBBBBB', 0, 6],
        ]);

        $this->assertCount(2, $stored);
        $this->assertSame([4, 6], array_column($stored, 'assembled'));
        $this->assertSame(
            [$this->path('first.bin'), $this->path('second.bin')],
            array_column($stored, 'path')
        );
    }

    /**
     * Send one request carrying a chunk of each of the given files.
     *
     * @param list<array{string, string, int, int}> $chunks name, bytes, which chunk, size of the whole file
     * @param list<ValidationInterface>              $validations
     *
     * @return list<array{path: string, range: array{int, int|null}, file: mixed, assembled: int}>
     */
    private function sendChunks(array $chunks, array $validations = []) : array
    {
        $_FILES = ['media' => ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []]];
        $_POST = ['range' => [], 'size' => []];

        foreach ($chunks as [$name, $bytes, $index, $total]) {
            $start = $index * self::CHUNK_SIZE;

            $_FILES['media']['name'][] = $name;
            $_FILES['media']['type'][] = 'application/octet-stream';
            $_FILES['media']['tmp_name'][] = $this->writeFile(uniqid('upload-', true), $bytes);
            $_FILES['media']['error'][] = UPLOAD_ERR_OK;
            $_FILES['media']['size'][] = strlen($bytes);

            $_POST['range'][] = sprintf('%d-%d', $start, $start + strlen($bytes) - 1);
            $_POST['size'][] = (string) $total;
        }

        return new StreamIn(
            'media',
            $this->workspace,
            new ChunkedUploadCollector('media'),
            $validations
        )->process();
    }
}

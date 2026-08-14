<?php

namespace Shetabit\Chunky\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Shetabit\Chunky\Classes\Collector;
use Shetabit\Chunky\Classes\StreamIn;
use Shetabit\Chunky\Tests\TestCase;

final class ChunkedUploadTest extends TestCase
{
    private const int CHUNK_SIZE = 4096;

    /**
     * @param list<int> $order the chunks are sent in this order
     */
    #[DataProvider('ordersTheChunksArriveIn')]
    public function testAFileThatIsSentChunkByChunkArrivesInOnePiece(array $order) : void
    {
        $contents = random_bytes(self::CHUNK_SIZE * 3 + 17); // three whole chunks and a rest
        $chunks = str_split($contents, self::CHUNK_SIZE);

        foreach ($order as $index) {
            $this->sendChunk('holiday.mp4', $chunks[$index], $index, strlen($contents));
        }

        $this->assertFileExists($this->path('holiday.mp4'));
        $this->assertSame($contents, file_get_contents($this->path('holiday.mp4')));
    }

    /**
     * @return array<string, array{list<int>}>
     */
    public static function ordersTheChunksArriveIn() : array
    {
        return [
            'one after the other' => [[0, 1, 2, 3]],
            'the other way around' => [[3, 2, 1, 0]],
            'out of order' => [[2, 0, 3, 1]],
            'the last one first' => [[3, 0, 1, 2]],
        ];
    }

    public function testAChunkThatIsSentTwiceDoesNotShowUpTwice() : void
    {
        $contents = random_bytes(self::CHUNK_SIZE * 2);
        $chunks = str_split($contents, self::CHUNK_SIZE);

        $this->sendChunk('holiday.mp4', $chunks[0], 0, strlen($contents));
        $this->sendChunk('holiday.mp4', $chunks[1], 1, strlen($contents));
        $this->sendChunk('holiday.mp4', $chunks[1], 1, strlen($contents)); // the client retried

        $this->assertSame($contents, file_get_contents($this->path('holiday.mp4')));
    }

    public function testSeveralFilesAreUploadedNextToEachOther() : void
    {
        $first = random_bytes(self::CHUNK_SIZE + 10);
        $second = random_bytes(self::CHUNK_SIZE + 20);

        foreach (str_split($first, self::CHUNK_SIZE) as $index => $chunk) {
            $this->sendChunk('first.bin', $chunk, $index, strlen($first));
        }

        foreach (str_split($second, self::CHUNK_SIZE) as $index => $chunk) {
            $this->sendChunk('second.bin', $chunk, $index, strlen($second));
        }

        $this->assertSame($first, file_get_contents($this->path('first.bin')));
        $this->assertSame($second, file_get_contents($this->path('second.bin')));
    }

    public function testAWholeFileIsUploadedInOneRequest() : void
    {
        $contents = random_bytes(1000);

        $this->fakeUpload('media', $contents, 'holiday.mp4');

        $stored = new StreamIn('media', $this->workspace)->process();

        $this->assertSame([0, 1000], $stored[0]['range']);
        $this->assertSame($contents, file_get_contents($this->path('holiday.mp4')));
    }

    public function testAFileIsUploadedAsBase64DataUrisChunkByChunk() : void
    {
        $contents = random_bytes(3000);
        $chunks = str_split($contents, 1000);

        foreach ($chunks as $index => $chunk) {
            $_FILES = [];
            $_REQUEST['media'] = $this->dataUri($chunk, 'video/mp4', [
                'name' => 'holiday.mp4',
                'range' => sprintf('%d-%d', $index * 1000, $index * 1000 + strlen($chunk) - 1),
                'size' => strlen($contents),
            ]);

            new StreamIn('media', $this->workspace)->process();
        }

        $this->assertSame($contents, file_get_contents($this->path('holiday.mp4')));
    }

    public function testAFileIsUploadedAsJsonPayloadsChunkByChunk() : void
    {
        $contents = random_bytes(3000);
        $chunks = str_split($contents, 1000);

        foreach ($chunks as $index => $chunk) {
            $_FILES = [];
            $_REQUEST['media'] = $this->jsonPayload($chunk, 'video/mp4', [
                'name' => 'holiday.mp4',
                'range' => sprintf('%d-%d', $index * 1000, $index * 1000 + strlen($chunk) - 1),
                'size' => strlen($contents),
            ]);

            new StreamIn('media', $this->workspace)->process();
        }

        $this->assertSame($contents, file_get_contents($this->path('holiday.mp4')));
    }

    public function testEveryFormatOfTheSameRequestIsUploadedAtOnce() : void
    {
        $this->fakeUpload('media', 'uploaded', 'uploaded.txt');
        $_REQUEST['media'] = [
            $this->dataUri('base64', 'text/plain', ['name' => 'base64.txt']),
            $this->jsonPayload('json', 'text/plain', ['name' => 'json.txt']),
        ];

        $stored = new StreamIn('media', $this->workspace)->process();

        $this->assertCount(3, $stored);
        $this->assertSame('uploaded', file_get_contents($this->path('uploaded.txt')));
        $this->assertSame('base64', file_get_contents($this->path('base64.txt')));
        $this->assertSame('json', file_get_contents($this->path('json.txt')));
    }

    public function testTheCollectedFilesCanBeStoredByHand() : void
    {
        $this->fakeUpload('media', 'hello', 'greeting.txt');

        $files = new Collector('media')->collect();

        $files[0]->saveAs($this->path('somewhere-else.txt'));

        $this->assertSame('hello', file_get_contents($this->path('somewhere-else.txt')));
        $this->assertSame('greeting.txt', $files[0]->name);
    }

    private function sendChunk(string $name, string $chunk, int $index, int $size) : void
    {
        $start = $index * self::CHUNK_SIZE;

        $_REQUEST = [];
        $this->fakeUpload('media', $chunk, $name, ['size' => $size]);

        // A browser announces the bytes of the whole file a chunk holds. The upload
        // itself carries no range, so it is sent along the request.
        $_REQUEST['range'] = sprintf('%d-%d', $start, $start + strlen($chunk) - 1);

        $collector = new class ('media') extends Collector {
            /**
             * @return list<\Shetabit\Chunky\Contracts\TempFileInterface>
             */
            public function collect() : array
            {
                $files = parent::collect();

                foreach ($files as $file) {
                    $file->range = $_REQUEST['range'] ?? null;
                }

                return $files;
            }
        };

        new StreamIn('media', $this->workspace, $collector)->process();
    }
}

<?php

namespace Shetabit\Chunky\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Shetabit\Chunky\Classes\ChunkedUploadCollector;
use Shetabit\Chunky\Classes\Collector;
use Shetabit\Chunky\Contracts\FileInterface;
use Shetabit\Chunky\Tests\TestCase;

#[CoversClass(ChunkedUploadCollector::class)]
final class ChunkedUploadCollectorTest extends TestCase
{
    public function testItIsACollector() : void
    {
        $this->assertInstanceOf(Collector::class, new ChunkedUploadCollector('media'));
    }

    public function testItReadsTheRangeAndTheSizeOfTheRequestByDefault() : void
    {
        $this->assertSame(
            ['range' => 'range', 'size' => 'size'],
            new ChunkedUploadCollector('media')->getOptions()
        );
    }

    public function testItHandsAChunkTheRangeThatCameWithTheRequest() : void
    {
        $this->fakeUpload('media', 'BBBB', 'holiday.mp4');
        $_POST = ['range' => '4-7', 'size' => '8'];

        $files = new ChunkedUploadCollector('media')->collect();

        $this->assertCount(1, $files);
        $this->assertSame('4-7', $files[0]->range);
        $this->assertSame('8', $files[0]->size);
    }

    public function testTheSizeOfTheRequestWinsOverTheSizeOfTheUpload() : void
    {
        $this->fakeUpload('media', 'BBBB', 'holiday.mp4'); // an upload of four bytes
        $_POST = ['size' => '1048576'];

        $files = new ChunkedUploadCollector('media')->collect();

        $this->assertSame('1048576', $files[0]->size);
    }

    public function testAFieldThatWasNotSentChangesNothing() : void
    {
        $this->fakeUpload('media', 'BBBB', 'holiday.mp4');

        $files = new ChunkedUploadCollector('media')->collect();

        $this->assertNull($files[0]->range);
        $this->assertSame(4, $files[0]->size); // the one $_FILES came with
    }

    public function testAnEmptyFieldChangesNothing() : void
    {
        $this->fakeUpload('media', 'BBBB', 'holiday.mp4');
        $_POST = ['range' => '', 'size' => '   '];

        $files = new ChunkedUploadCollector('media')->collect();

        $this->assertNull($files[0]->range);
        $this->assertSame(4, $files[0]->size);
    }

    public function testASingleValueBelongsToEveryChunkOfTheRequest() : void
    {
        $_REQUEST['media'] = [$this->dataUri('one'), $this->dataUri('two')];
        $_POST = ['range' => '0-2'];

        $files = new ChunkedUploadCollector('media')->collect();

        $this->assertCount(2, $files);
        $this->assertSame('0-2', $files[0]->range);
        $this->assertSame('0-2', $files[1]->range);
    }

    public function testAListOfValuesBelongsToTheChunksOneByOne() : void
    {
        $first = $this->writeFile('first', 'AAAA');
        $second = $this->writeFile('second', 'BBBB');

        $_FILES['media'] = [
            'name' => ['first.bin', 'second.bin'],
            'type' => ['application/octet-stream', 'application/octet-stream'],
            'tmp_name' => [$first, $second],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [4, 4],
        ];

        $_POST = [
            'range' => ['0-3', '4-7'],
            'size' => ['8', '16'],
        ];

        $files = new ChunkedUploadCollector('media')->collect();

        $this->assertCount(2, $files);
        $this->assertSame(['0-3', '4-7'], [$files[0]->range, $files[1]->range]);
        $this->assertSame(['8', '16'], [$files[0]->size, $files[1]->size]);
    }

    public function testAListWithNothingForAChunkLeavesThatChunkAlone() : void
    {
        $_REQUEST['media'] = [$this->dataUri('one'), $this->dataUri('two')];
        $_POST = ['range' => ['0-2']]; // only the first one

        $files = new ChunkedUploadCollector('media')->collect();

        $this->assertSame('0-2', $files[0]->range);
        $this->assertNull($files[1]->range);
    }

    public function testItCanBeToldWhichAttributesToRead() : void
    {
        $this->fakeUpload('media', 'BBBB', 'holiday.mp4');
        $_POST = ['name' => 'renamed.mp4', 'mime' => 'video/mp4', 'range' => '0-3'];

        $files = new ChunkedUploadCollector('media', ['name', 'mime'])->collect();

        $this->assertSame('renamed.mp4', $files[0]->name);
        $this->assertSame('video/mp4', $files[0]->mime);
        $this->assertNull($files[0]->range, 'range was not among the attributes that were asked for');
    }

    public function testAnAttributeCanBeReadOutOfAFieldOfAnotherName() : void
    {
        $this->fakeUpload('media', 'BBBB', 'holiday.mp4');
        $_POST = ['chunk_range' => '4-7', 'total_bytes' => '64'];

        $collector = new ChunkedUploadCollector('media', [
            'range' => 'chunk_range',
            'size' => 'total_bytes',
        ]);

        $files = $collector->collect();

        $this->assertSame(['range' => 'chunk_range', 'size' => 'total_bytes'], $collector->getOptions());
        $this->assertSame('4-7', $files[0]->range);
        $this->assertSame('64', $files[0]->size);
    }

    public function testItReadsTheFieldsOfTheSourceItWasGiven() : void
    {
        $this->fakeUpload('media', 'BBBB', 'holiday.mp4');
        $_POST = ['range' => '0-3'];

        $files = new ChunkedUploadCollector('media', source: ['range' => '8-11'])->collect();

        $this->assertSame('8-11', $files[0]->range);
    }

    public function testAFieldThatIsNoScalarIsLeftAlone() : void
    {
        $this->fakeUpload('media', 'BBBB', 'holiday.mp4');
        $_POST = ['range' => ['nested' => ['0-3']]];

        $files = new ChunkedUploadCollector('media')->collect();

        $this->assertNull($files[0]->range);
    }

    public function testItCollectsEveryFormatTheCollectorDoes() : void
    {
        $this->fakeUpload('media', 'uploaded', 'uploaded.txt');
        $_REQUEST['media'] = [$this->dataUri('base64'), $this->jsonPayload('json')];
        $_POST = ['size' => '99'];

        $files = new ChunkedUploadCollector('media')->collect();

        $this->assertSame(['uploaded', 'base64', 'json'], $this->contentsOf($files));
        $this->assertSame(['99', '99', '99'], array_map(static fn (FileInterface $f): mixed => $f->size, $files));
    }
}

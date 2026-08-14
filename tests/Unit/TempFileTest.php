<?php

namespace Shetabit\Chunky\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Shetabit\Chunky\Classes\File;
use Shetabit\Chunky\Classes\TempFile;
use Shetabit\Chunky\Tests\TestCase;

#[CoversClass(TempFile::class)]
final class TempFileTest extends TestCase
{
    public function testItCreatesATemporaryFileOfItsOwn() : void
    {
        $file = new TempFile();

        $this->assertSame('', $file->getPath());

        $file->create('hello');

        $this->assertNotSame('', $file->getPath());
        $this->assertTrue($file->exists());
        $this->assertSame('hello', $file->read());
    }

    public function testTheTemporaryFileIsRemovedWhenItIsClosed() : void
    {
        $file = new TempFile();
        $file->create('hello');

        $path = $file->getPath();

        $this->assertFileExists($path);

        $file->close();

        $this->assertFileDoesNotExist($path);
    }

    public function testItKeepsTheSameTemporaryFileWhenItIsWrittenAgain() : void
    {
        $file = new TempFile();

        $file->create('first');
        $path = $file->getPath();

        $file->create('second');

        $this->assertSame($path, $file->getPath());
        $this->assertSame('second', $file->read());
    }

    public function testItKeepsThePathItWasGiven() : void
    {
        $file = new TempFile($this->path('given.bin'));

        $file->create('hello');

        $this->assertSame($this->path('given.bin'), $file->getPath());
        $this->assertSame('hello', file_get_contents($this->path('given.bin')));
    }

    public function testItSavesItselfUnderTheGivenPath() : void
    {
        $file = new TempFile($this->writeFile('chunk.bin', 'hello'));

        $this->assertSame($file, $file->saveAs($this->path('saved.bin')));
        $this->assertSame('hello', file_get_contents($this->path('saved.bin')));
    }

    public function testItSavesItselfAtTheGivenOffsetOfTheDestination() : void
    {
        new TempFile($this->writeFile('chunk.bin', 'BBB'))->saveAs($this->path('saved.bin'), 3);

        $this->assertSame("\0\0\0BBB", file_get_contents($this->path('saved.bin')));
    }

    /**
     * The offset says where the chunk belongs in the destination, so the chunk itself
     * is always read from its own beginning. It used to be read from the offset as
     * well, which left every chunk but the first one empty.
     */
    public function testItReadsTheChunkFromItsOwnBeginning() : void
    {
        new TempFile($this->writeFile('chunk.bin', 'BBB'))->saveAs($this->path('saved.bin'), 1024);

        $this->assertSame('BBB', substr((string) file_get_contents($this->path('saved.bin')), 1024));
        $this->assertSame(1027, filesize($this->path('saved.bin')));
    }

    public function testItSavesOnlyTheGivenNumberOfBytes() : void
    {
        new TempFile($this->writeFile('chunk.bin', 'abcdefghij'))->saveAs($this->path('saved.bin'), 0, 4);

        $this->assertSame('abcd', file_get_contents($this->path('saved.bin')));
    }

    public function testALengthLongerThanTheChunkSavesTheWholeChunk() : void
    {
        new TempFile($this->writeFile('chunk.bin', 'abc'))->saveAs($this->path('saved.bin'), 0, 4096);

        $this->assertSame('abc', file_get_contents($this->path('saved.bin')));
    }

    /**
     * Chunks of one file, stored one after the other into the same destination.
     */
    public function testChunksAreAssembledIntoOneFileWhateverOrderTheyArriveIn() : void
    {
        $destination = $this->path('assembled.bin');

        new TempFile($this->writeFile('chunk-3.bin', 'CCCC'))->saveAs($destination, 8, 4);
        new TempFile($this->writeFile('chunk-1.bin', 'AAAA'))->saveAs($destination, 0, 4);
        new TempFile($this->writeFile('chunk-2.bin', 'BBBB'))->saveAs($destination, 4, 4);

        $this->assertSame('AAAABBBBCCCC', file_get_contents($destination));
    }

    public function testItDoesNotKeepTheDestinationOpen() : void
    {
        $file = new TempFile($this->writeFile('chunk.bin', 'hello'));
        $file->read(); // the chunk itself keeps a handle around, the destination must not

        $before = count(get_resources('stream'));

        $file->saveAs($this->path('saved.bin'));
        $file->saveAs($this->path('saved.bin'), 5);

        $this->assertCount($before, get_resources('stream'));
    }

    public function testItIsAFile() : void
    {
        $this->assertInstanceOf(File::class, new TempFile());
    }
}

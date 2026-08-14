<?php

namespace Shetabit\Chunky\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Shetabit\Chunky\Classes\File;
use Shetabit\Chunky\Exceptions\FileAccessException;
use Shetabit\Chunky\Exceptions\FileNotFoundException;
use Shetabit\Chunky\Tests\TestCase;

#[CoversClass(File::class)]
final class FileTest extends TestCase
{
    public function testItCreatesAFileWithTheGivenData() : void
    {
        $file = new File($this->path('created.txt'));

        $this->assertSame($file, $file->create('hello'));
        $this->assertSame('hello', file_get_contents($this->path('created.txt')));
    }

    public function testItOverwritesAFileThatIsAlreadyThere() : void
    {
        $this->writeFile('created.txt', 'a long previous content');

        new File($this->path('created.txt'))->create('short');

        $this->assertSame('short', file_get_contents($this->path('created.txt')));
    }

    public function testItReadsTheWholeFileByDefault() : void
    {
        $file = new File($this->writeFile('read.txt', 'abcdefghij'));

        $this->assertSame('abcdefghij', $file->read());
    }

    public function testItReadsFromTheGivenOffset() : void
    {
        $file = new File($this->writeFile('read.txt', 'abcdefghij'));

        $this->assertSame('defghij', $file->read(3));
        $this->assertSame('def', $file->read(3, 3));
    }

    public function testItReadsTheSameFileTwice() : void
    {
        $file = new File($this->writeFile('read.txt', 'abcdefghij'));

        $this->assertSame('abc', $file->read(0, 3));
        $this->assertSame('abc', $file->read(0, 3));
    }

    /**
     * @param int|null $length a length that says "read whatever is left"
     */
    #[DataProvider('lengthsThatReadEverything')]
    public function testALengthThatIsNotPositiveOrTooLongReadsTheRestOfTheFile(int|null $length) : void
    {
        $file = new File($this->writeFile('read.txt', 'abcdefghij'));

        $this->assertSame('abcdefghij', $file->read(0, $length));
    }

    /**
     * @return array<string, array{int|null}>
     */
    public static function lengthsThatReadEverything() : array
    {
        return [
            'no length' => [null],
            'zero' => [0],
            'negative' => [-5],
            'longer than the file' => [1000],
        ];
    }

    public function testItReadsNothingBehindTheEndOfTheFile() : void
    {
        $file = new File($this->writeFile('read.txt', 'abcdefghij'));

        $this->assertSame('', $file->read(10));
        $this->assertSame('', $file->read(50));
    }

    public function testANegativeOffsetReadsFromTheBeginning() : void
    {
        $file = new File($this->writeFile('read.txt', 'abcdefghij'));

        $this->assertSame('abc', $file->read(-10, 3));
    }

    public function testReadingAFileThatDoesNotExistThrows() : void
    {
        $file = new File($this->path('missing.txt'));

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('missing.txt" does not exist');

        $file->read();
    }

    public function testItWritesAtTheGivenOffset() : void
    {
        $file = new File($this->writeFile('write.txt', 'aaaaaaaaaa'));

        $this->assertSame($file, $file->write('XY', 4));

        $this->assertSame('aaaaXYaaaa', file_get_contents($this->path('write.txt')));
    }

    public function testItWritesChunksThatArriveOutOfOrderAtTheirOwnOffset() : void
    {
        $file = new File($this->path('assembled.bin'));

        $file->write('CCC', 6);
        $file->write('AAA', 0);
        $file->write('BBB', 3);

        $this->assertSame('AAABBBCCC', file_get_contents($this->path('assembled.bin')));
    }

    public function testWritingBehindTheEndOfTheFileLeavesAGap() : void
    {
        $file = new File($this->path('sparse.bin'));

        $file->write('A', 0);
        $file->write('B', 4);

        $this->assertSame("A\0\0\0B", file_get_contents($this->path('sparse.bin')));
        $this->assertSame(5, $file->getSize());
    }

    public function testANegativeOffsetWritesToTheBeginning() : void
    {
        $file = new File($this->writeFile('write.txt', 'aaaa'));

        $file->write('X', -3);

        $this->assertSame('Xaaa', file_get_contents($this->path('write.txt')));
    }

    public function testItCreatesTheFileItWritesTo() : void
    {
        $file = new File($this->path('new.txt'));

        $this->assertFalse($file->exists());

        $file->write('data');

        $this->assertTrue($file->exists());
    }

    public function testItReportsTheSizeOfTheFile() : void
    {
        $file = new File($this->writeFile('size.txt', 'abcde'));

        $this->assertSame(5, $file->getSize());
    }

    public function testTheSizeIsUpToDateRightAfterAWrite() : void
    {
        $file = new File($this->writeFile('size.txt', 'abcde'));

        $this->assertSame(5, $file->getSize());

        $file->write('fghij', 5);

        $this->assertSame(10, $file->getSize());
    }

    public function testTheSizeOfAFileThatDoesNotExistIsZero() : void
    {
        $this->assertSame(0, new File($this->path('missing.txt'))->getSize());
        $this->assertSame(0, new File()->getSize());
    }

    public function testItRemovesTheFile() : void
    {
        $file = new File($this->writeFile('remove.txt', 'data'));

        $this->assertSame($file, $file->remove());
        $this->assertFalse($file->exists());
        $this->assertFileDoesNotExist($this->path('remove.txt'));
    }

    public function testRemovingAFileThatIsNotThereDoesNothing() : void
    {
        $file = new File($this->path('missing.txt'));

        $this->assertSame($file, $file->remove());
    }

    public function testItReportsItsPath() : void
    {
        $this->assertSame($this->path('a.txt'), new File($this->path('a.txt'))->getPath());
        $this->assertSame('', new File()->getPath());
    }

    public function testAFileWithoutAPathCanNotBeOpened() : void
    {
        $this->expectException(FileAccessException::class);
        $this->expectExceptionMessage('has no path');

        new File()->create('data');
    }

    public function testAFileThatCanNotBeOpenedThrows() : void
    {
        $file = new File($this->path('no-such-directory/file.txt'));

        $this->expectException(FileAccessException::class);
        $this->expectExceptionMessage('could not be opened');

        $file->create('data');
    }

    public function testClosingTheFileTwiceIsFine() : void
    {
        $file = new File($this->writeFile('close.txt', 'data'));

        $file->read();

        $this->assertSame($file, $file->close());
        $this->assertSame($file, $file->close());

        // and it can be read again afterwards
        $this->assertSame('data', $file->read());
    }

    public function testItKeepsASingleHandleAround() : void
    {
        $file = new File($this->writeFile('handles.txt', 'data'));

        $before = count(get_resources('stream'));

        for ($i = 0; $i < 20; $i++) {
            $file->read();
            $file->write('data');
        }

        $this->assertCount($before + 1, get_resources('stream'));
    }

    public function testItClosesItsHandleWhenItIsCollected() : void
    {
        $file = new File($this->writeFile('handles.txt', 'data'));
        $file->read();

        $before = count(get_resources('stream'));

        unset($file);

        $this->assertCount($before - 1, get_resources('stream'));
    }

    public function testItStoresAttributeInformation() : void
    {
        $file = new File($this->path('a.txt'));

        $this->assertFalse(isset($file->name));
        $this->assertNull($file->name);

        $file->name = 'avatar.png';
        $file->size = 1024;

        $this->assertTrue(isset($file->name));
        $this->assertSame('avatar.png', $file->name);
        $this->assertSame(['name' => 'avatar.png', 'size' => 1024], $file->getAttributes());

        unset($file->name);

        $this->assertFalse(isset($file->name));
        $this->assertSame(['size' => 1024], $file->getAttributes());
    }
}

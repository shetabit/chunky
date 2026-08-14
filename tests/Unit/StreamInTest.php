<?php

namespace Shetabit\Chunky\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Shetabit\Chunky\Classes\Collector;
use Shetabit\Chunky\Classes\StreamIn;
use Shetabit\Chunky\Classes\TempFile;
use Shetabit\Chunky\Exceptions\FileNotFoundException;
use Shetabit\Chunky\Tests\TestCase;

#[CoversClass(StreamIn::class)]
final class StreamInTest extends TestCase
{
    public function testItReportsWhereItStoresTheChunks() : void
    {
        $this->assertSame($this->workspace, new StreamIn('media', $this->workspace)->getPath());
    }

    public function testItRefusesADirectoryThatDoesNotExist() : void
    {
        $stream = new StreamIn('media', $this->path('nope'));

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('upload directory');

        $stream->process();
    }

    public function testItStoresAnUploadedFile() : void
    {
        $this->fakeUpload('media', 'hello', 'greeting.txt');

        $stored = new StreamIn('media', $this->workspace)->process();

        $this->assertCount(1, $stored);
        $this->assertSame($this->path('greeting.txt'), $stored[0]['path']);
        $this->assertSame([0, 5], $stored[0]['range']);
        $this->assertSame('hello', file_get_contents($this->path('greeting.txt')));
    }

    public function testItRemembersTheChunksOfTheLastRun() : void
    {
        $this->fakeUpload('media', 'hello', 'greeting.txt');

        $stream = new StreamIn('media', $this->workspace);

        $this->assertSame([], $stream->getFiles());

        $stream->process();

        $this->assertCount(1, $stream->getFiles());
    }

    public function testItSkipsAChunkWithoutAName() : void
    {
        $_REQUEST['media'] = $this->dataUri('hello'); // a data uri without a name in its meta section

        $this->assertSame([], new StreamIn('media', $this->workspace)->process());
    }

    public function testItTakesTheCollectorItIsGiven() : void
    {
        $this->fakeUpload('other', 'hello', 'greeting.txt');

        $stored = new StreamIn('media', $this->workspace, new Collector('other'))->process();

        $this->assertCount(1, $stored);
    }

    /**
     * The name of a chunk is whatever the client sent, so a name that points out of
     * the upload directory has to be cut down to its file name.
     */
    #[DataProvider('namesThatPointSomewhereElse')]
    public function testItStoresAChunkUnderItsFileNameOnly(string $name, string|null $expected) : void
    {
        $file = new TempFile();
        $file->name = $name;

        $this->assertSame($expected, new StreamIn('media', $this->workspace)->getName($file));
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function namesThatPointSomewhereElse() : array
    {
        return [
            'a plain name' => ['avatar.png', 'avatar.png'],
            'a relative path' => ['../../etc/passwd', 'passwd'],
            'an absolute path' => ['/etc/passwd', 'passwd'],
            'a windows path' => ['..\\..\\windows\\system.ini', 'system.ini'],
            'a directory' => ['uploads/', 'uploads'],
            'surrounded by spaces' => ['  avatar.png  ', 'avatar.png'],
            'nothing but a slash' => ['/', null],
            'the current directory' => ['.', null],
            'the parent directory' => ['..', null],
            'an empty name' => ['', null],
        ];
    }

    public function testAChunkWhoseNameIsNoStringHasNoName() : void
    {
        $file = new TempFile();
        $file->name = 42;

        $this->assertNull(new StreamIn('media', $this->workspace)->getName($file));
    }

    public function testAPathTraversingNameStaysInTheUploadDirectory() : void
    {
        $this->fakeUpload('media', 'gotcha', '../escaped.txt');

        $stored = new StreamIn('media', $this->workspace)->process();

        $this->assertSame($this->path('escaped.txt'), $stored[0]['path']);
        $this->assertFileDoesNotExist(dirname($this->workspace).DIRECTORY_SEPARATOR.'escaped.txt');
    }

    /**
     * @param array{int, int|null} $expected
     */
    #[DataProvider('ranges')]
    public function testItReadsWhereAChunkBelongs(mixed $range, mixed $size, array $expected) : void
    {
        $file = new TempFile();
        $file->range = $range;
        $file->size = $size;

        $this->assertSame($expected, new StreamIn('media', $this->workspace)->getRange($file));
    }

    /**
     * @return array<string, array{mixed, mixed, array{int, int|null}}>
     */
    public static function ranges() : array
    {
        return [
            'no range at all' => [null, 2048, [0, 2048]],
            'an empty range' => ['', 2048, [0, 2048]],
            'the first chunk' => ['0-1023', 4096, [0, 1024]],
            'a chunk in the middle' => ['1024-2047', 4096, [1024, 1024]],
            'a single byte' => ['5-5', 4096, [5, 1]],
            'a range without a size' => ['0-99', null, [0, 100]],
            'no range and no size' => [null, null, [0, null]],
            'a size that is a string' => [null, '2048', [0, 2048]],
            'a range that is turned around' => ['2047-1024', 4096, [0, 4096]],
            'a range that is no range' => ['nonsense', 4096, [0, 4096]],
            'a range with too many ends' => ['0-1-2', 4096, [0, 4096]],
            'a range that is no string' => [1024, 4096, [0, 4096]],
            'a negative start' => ['-1024', 4096, [0, 4096]],
        ];
    }

    public function testItStoresAChunkAtTheOffsetOfItsRange() : void
    {
        $_REQUEST['media'] = $this->dataUri('BBBB', 'text/plain', ['name' => 'file.bin', 'range' => '4-7']);

        $stored = new StreamIn('media', $this->workspace)->process();

        $this->assertSame([4, 4], $stored[0]['range']);
        $this->assertSame("\0\0\0\0BBBB", file_get_contents($this->path('file.bin')));
    }
}

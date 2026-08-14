<?php

namespace Shetabit\Chunky\Tests\Unit\Validations;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Shetabit\Chunky\Classes\TempFile;
use Shetabit\Chunky\Exceptions\ValidationFailedException;
use Shetabit\Chunky\Tests\TestCase;
use Shetabit\Chunky\Validations\Size;

#[CoversClass(Size::class)]
final class SizeTest extends TestCase
{
    #[DataProvider('sizes')]
    public function testItReadsASizeTheWayAPersonWritesIt(int|string $written, int $bytes) : void
    {
        $this->assertSame($bytes, Size::toBytes($written));
    }

    /**
     * @return array<string, array{int|string, int}>
     */
    public static function sizes() : array
    {
        return [
            'a number' => [2048, 2048],
            'a number as a string' => ['2048', 2048],
            'bytes' => ['512b', 512],
            'kilobytes' => ['5k', 5 * 1024],
            'kilobytes, spelled out' => ['5KB', 5 * 1024],
            'megabytes' => ['5M', 5 * 1024 * 1024],
            'gigabytes' => ['2g', 2 * 1024 ** 3],
            'terabytes' => ['1T', 1024 ** 4],
            'petabytes' => ['1p', 1024 ** 5],
            'with a space' => ['5 MB', 5 * 1024 * 1024],
            'a fraction' => ['1.5M', (int) (1.5 * 1024 * 1024)],
            'nothing' => ['', 0],
            'nonsense' => ['what', 0],
        ];
    }

    public function testItTakesAFileThatIsSmallEnough() : void
    {
        $file = new TempFile($this->writeFile('chunk.bin', str_repeat('a', 100)));
        $file->size = 100;

        $this->expectNotToPerformAssertions();

        new Size('1K')->validate($file);
    }

    public function testItTurnsAwayAFileThatIsTooLarge() : void
    {
        $file = new TempFile($this->writeFile('chunk.bin', 'a'));
        $file->name = 'holiday.mp4';
        $file->size = 6 * 1024 * 1024; // the client says the whole file is 6 MB

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('The file is too large: 6291456 bytes, and 5242880 is the most that is taken.');

        new Size('5M')->validate($file);
    }

    /**
     * A file is turned away at its first chunk, before any of it has been written.
     */
    public function testItTurnsAFileAwayByTheSizeItAnnouncedNotByTheChunkThatArrived() : void
    {
        $file = new TempFile($this->writeFile('chunk.bin', str_repeat('a', 10)));
        $file->size = 10_000;
        $file->range = '0-9';

        $this->expectException(ValidationFailedException::class);

        new Size(5000)->validate($file);
    }

    /**
     * A client that announces a small file and then keeps sending is caught chunk by
     * chunk, whatever it said at the start.
     */
    public function testItTurnsAwayAChunkThatIsLargerThanTheMaximumOnItsOwn() : void
    {
        $file = new TempFile($this->writeFile('chunk.bin', str_repeat('a', 200)));
        $file->size = 10; // ... and the chunk that came with it is 200 bytes

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('The chunk is too large: 200 bytes');

        new Size(100)->validate($file);
    }

    public function testItTurnsAwayAFileThatIsTooSmall() : void
    {
        $file = new TempFile($this->writeFile('chunk.bin', 'ab'));
        $file->size = 2;

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('The file is too small: 2 bytes, and 1024 is the least that is taken.');

        new Size('5M', '1K')->validate($file);
    }

    public function testAFileWithoutAnAnnouncedSizeIsMeasuredByWhatArrived() : void
    {
        $file = new TempFile($this->writeFile('chunk.bin', str_repeat('a', 200)));

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('The file is too large: 200 bytes');

        new Size(100)->validate($file);
    }

    public function testItCarriesTheFileItTurnedAway() : void
    {
        $file = new TempFile($this->writeFile('chunk.bin', str_repeat('a', 200)));

        try {
            new Size(100)->validate($file);

            $this->fail('The validation should have thrown.');
        } catch (ValidationFailedException $exception) {
            $this->assertSame($file, $exception->getChunk());
            $this->assertCount(1, $exception->getErrors());
        }
    }

    public function testItReportsItsBounds() : void
    {
        $size = new Size('5M', '1K');

        $this->assertSame(5 * 1024 * 1024, $size->getMaximum());
        $this->assertSame(1024, $size->getMinimum());
    }
}

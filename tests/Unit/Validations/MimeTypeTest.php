<?php

namespace Shetabit\Chunky\Tests\Unit\Validations;

use PHPUnit\Framework\Attributes\CoversClass;
use Shetabit\Chunky\Classes\TempFile;
use Shetabit\Chunky\Exceptions\ValidationFailedException;
use Shetabit\Chunky\Tests\TestCase;
use Shetabit\Chunky\Validations\MimeType;

#[CoversClass(MimeType::class)]
final class MimeTypeTest extends TestCase
{
    /**
     * The first bytes of a gif, which is what `finfo` reads a file's type out of.
     */
    private const string GIF = "GIF89a\x01\x00\x01\x00\x00\xff\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x00;";

    public function testItTakesAFileOfAnAllowedType() : void
    {
        $file = $this->chunk($this::GIF, 'image/gif');

        $this->expectNotToPerformAssertions();

        new MimeType(['image/gif', 'image/png'])->validate($file);
    }

    public function testItTurnsAwayAFileOfAnotherType() : void
    {
        $file = $this->chunk('anything', 'application/pdf');

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Files of the type "application/pdf" are not taken, only image/gif.');

        new MimeType(['image/gif'])->validate($file);
    }

    public function testItTurnsAwayAFileThatSaysNothingAboutItsType() : void
    {
        $file = new TempFile($this->writeFile('chunk.bin', 'anything'));

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Files of the type "unknown" are not taken');

        new MimeType(['image/gif'])->validate($file);
    }

    public function testTheTypesMayBeWrittenInAnyCase() : void
    {
        $file = $this->chunk($this::GIF, 'IMAGE/GIF');

        new MimeType([' Image/GIF '])->validate($file); // it would throw if it did not take the file

        $this->assertSame(['image/gif'], new MimeType([' Image/GIF '])->getAllowed());
    }

    /**
     * A client can call a file whatever it likes, so the bytes have a say too.
     */
    public function testItTurnsAwayAFileThatIsNotWhatItSaysItIs() : void
    {
        $file = $this->chunk('<?php echo "hello";', 'image/gif');

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('The file says it is "image/gif", and it is "text/x-php".');

        new MimeType(['image/gif'])->validate($file);
    }

    public function testItTakesTheClientsWordForItWhenItIsAskedTo() : void
    {
        $file = $this->chunk('<?php echo "hello";', 'image/gif');

        $this->expectNotToPerformAssertions();

        new MimeType(['image/gif'], detect: false)->validate($file);
    }

    /**
     * A chunk out of the middle of a file has none of the bytes that give a file
     * away, so there is nothing to look at and nothing to complain about.
     */
    public function testItDoesNotLookIntoAChunkThatIsNotTheFirstOne() : void
    {
        $file = $this->chunk('the middle of a video, which looks like nothing', 'video/mp4');
        $file->range = '4096-8191';

        $this->expectNotToPerformAssertions();

        new MimeType(['video/mp4'])->validate($file);
    }

    public function testItLooksIntoAChunkThatHoldsTheBeginningOfItsFile() : void
    {
        $file = $this->chunk('<?php echo "hello";', 'image/gif');
        $file->range = '0-4095';

        $this->expectException(ValidationFailedException::class);

        new MimeType(['image/gif'])->validate($file);
    }

    public function testAFileWithNothingInItIsTakenAtItsWord() : void
    {
        $file = $this->chunk('', 'image/gif');

        $this->expectNotToPerformAssertions();

        new MimeType(['image/gif'])->validate($file);
    }

    /**
     * A chunk with the given content, that says it is of the given type.
     */
    private function chunk(string $contents, string $mime) : TempFile
    {
        $file = new TempFile($this->writeFile(uniqid('chunk-', true), $contents));

        $file->name = 'holiday'.$mime;
        $file->mime = $mime;

        return $file;
    }
}

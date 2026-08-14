<?php

namespace Shetabit\Chunky\Tests\Unit\Validations;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Shetabit\Chunky\Classes\TempFile;
use Shetabit\Chunky\Exceptions\ValidationFailedException;
use Shetabit\Chunky\Tests\TestCase;
use Shetabit\Chunky\Validations\Extension;

#[CoversClass(Extension::class)]
final class ExtensionTest extends TestCase
{
    #[DataProvider('namesThatAreTaken')]
    public function testItTakesAFileWhoseNameEndsInAnAllowedExtension(string $name) : void
    {
        $file = new TempFile();
        $file->name = $name;

        $this->expectNotToPerformAssertions();

        new Extension(['jpg', 'jpeg', 'png'])->validate($file);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function namesThatAreTaken() : array
    {
        return [
            'a plain name' => ['avatar.png'],
            'upper case' => ['AVATAR.PNG'],
            'mixed case' => ['Avatar.PnG'],
            'several dots' => ['my.holiday.photo.jpg'],
            'another allowed one' => ['photo.jpeg'],
        ];
    }

    public function testItTurnsAwayAFileOfAnotherType() : void
    {
        $file = new TempFile();
        $file->name = 'invoice.pdf';

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Files of the type "pdf" are not taken, only jpg, jpeg, png.');

        new Extension(['jpg', 'jpeg', 'png'])->validate($file);
    }

    public function testItTurnsAwayAFileWithoutAnExtension() : void
    {
        $file = new TempFile();
        $file->name = 'holiday';

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('The file has no extension');

        new Extension(['jpg'])->validate($file);
    }

    public function testItTurnsAwayAFileWithoutAName() : void
    {
        $this->expectException(ValidationFailedException::class);

        new Extension(['jpg'])->validate(new TempFile());
    }

    public function testTheExtensionsMayBeWrittenWithTheirDotAndInAnyCase() : void
    {
        $extension = new Extension(['.JPG', ' png ']);

        $file = new TempFile();
        $file->name = 'avatar.jpg';

        $extension->validate($file); // it would throw if it did not take the file

        $this->assertSame(['jpg', 'png'], $extension->getAllowed());
    }

    public function testAnEmptyListTakesNothing() : void
    {
        $file = new TempFile();
        $file->name = 'avatar.png';

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('only nothing');

        new Extension([])->validate($file);
    }
}

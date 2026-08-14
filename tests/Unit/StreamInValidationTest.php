<?php

namespace Shetabit\Chunky\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use Shetabit\Chunky\Classes\StreamIn;
use Shetabit\Chunky\Contracts\FileInterface;
use Shetabit\Chunky\Contracts\ValidationInterface;
use Shetabit\Chunky\Exceptions\ValidationFailedException;
use Shetabit\Chunky\Tests\Fixtures\RefusingValidation;
use Shetabit\Chunky\Tests\TestCase;
use Shetabit\Chunky\Traits\HasUploadEvents;
use Shetabit\Chunky\Traits\Validates;
use Shetabit\Chunky\Validations\Extension;
use Shetabit\Chunky\Validations\Size;

#[CoversClass(StreamIn::class)]
#[CoversTrait(Validates::class)]
#[CoversTrait(HasUploadEvents::class)]
final class StreamInValidationTest extends TestCase
{
    public function testItTakesTheValidationsOfItsConstructor() : void
    {
        $validation = new RefusingValidation();

        $stream = new StreamIn('media', $this->workspace, null, [$validation]);

        $this->assertSame([$validation], $stream->getValidations());
    }

    public function testValidationsCanBeAddedAfterwards() : void
    {
        $stream = new StreamIn('media', $this->workspace);
        $first = new RefusingValidation();
        $second = new RefusingValidation();

        $this->assertSame([], $stream->getValidations());
        $this->assertSame($stream, $stream->addValidation($first));
        $this->assertSame($stream, $stream->addValidations([$second]));

        $this->assertSame([$first, $second], $stream->getValidations());
    }

    public function testAChunkThatPassesIsStored() : void
    {
        $this->fakeUpload('media', 'hello', 'greeting.txt');

        $stored = new StreamIn('media', $this->workspace, null, [new Extension(['txt'])])->process();

        $this->assertCount(1, $stored);
        $this->assertSame('hello', file_get_contents($this->path('greeting.txt')));
    }

    public function testAChunkThatIsTurnedAwayIsNotStored() : void
    {
        $this->fakeUpload('media', 'hello', 'invoice.pdf');

        $stream = new StreamIn('media', $this->workspace, null, [new Extension(['txt'])]);

        try {
            $stream->process();

            $this->fail('The stream should have thrown.');
        } catch (ValidationFailedException $exception) {
            $this->assertStringContainsString(
                'invoice.pdf: Files of the type "pdf" are not taken',
                $exception->getMessage()
            );
        }

        $this->assertFileDoesNotExist($this->path('invoice.pdf'));
    }

    /**
     * A request that carries one chunk too many leaves nothing of itself behind.
     */
    public function testNothingIsStoredWhenASingleChunkIsTurnedAway() : void
    {
        $_REQUEST['media'] = [
            $this->dataUri('one', 'text/plain', ['name' => 'first.txt']),
            $this->dataUri('two', 'application/pdf', ['name' => 'second.pdf']),
        ];

        $stream = new StreamIn('media', $this->workspace, null, [new Extension(['txt'])]);

        $this->expectException(ValidationFailedException::class);

        try {
            $stream->process();
        } finally {
            $this->assertFileDoesNotExist($this->path('first.txt'));
            $this->assertFileDoesNotExist($this->path('second.pdf'));
        }
    }

    public function testItReportsEverythingThatIsWrongAtOnce() : void
    {
        $_REQUEST['media'] = [
            $this->dataUri('one', 'application/pdf', ['name' => 'first.pdf']),
            $this->dataUri(str_repeat('a', 500), 'application/pdf', ['name' => 'second.pdf']),
        ];

        $stream = new StreamIn('media', $this->workspace, null, [new Extension(['txt']), new Size(100)]);

        try {
            $stream->process();

            $this->fail('The stream should have thrown.');
        } catch (ValidationFailedException $exception) {
            // the first file is of the wrong type, the second one is that and too large
            $this->assertCount(3, $exception->getErrors());
            $this->assertNull($exception->getChunk());
        }
    }

    public function testTheValidationsCanBeRunWithoutStoringAnything() : void
    {
        $this->fakeUpload('media', 'hello', 'invoice.pdf');

        $stream = new StreamIn('media', $this->workspace, null, [new Extension(['txt'])]);

        $this->expectException(ValidationFailedException::class);

        $stream->validate();
    }

    public function testItCallsEveryEventInOrder() : void
    {
        $this->fakeUpload('media', 'hello', 'greeting.txt');

        $called = [];
        $stream = new StreamIn('media', $this->workspace);

        $stream
            ->beforeValidate(static function (FileInterface $file) use (&$called): void {
                $called[] = 'beforeValidate:'.$file->name;
            })
            ->afterValidate(static function (FileInterface $file, array $errors) use (&$called): void {
                $called[] = 'afterValidate:'.count($errors);
            })
            ->beforeUpload(static function (FileInterface $file, string $path) use (&$called): void {
                $called[] = 'beforeUpload:'.basename($path);
            })
            ->afterUpload(static function (FileInterface $file, string $path, int $assembled) use (&$called): void {
                $called[] = 'afterUpload:'.$assembled;
            });

        $stream->process();

        $this->assertSame([
            'beforeValidate:greeting.txt',
            'afterValidate:0',
            'beforeUpload:greeting.txt',
            'afterUpload:5',
        ], $called);
    }

    public function testTheValidationEventsAreCalledEvenWhenAChunkIsTurnedAway() : void
    {
        $this->fakeUpload('media', 'hello', 'invoice.pdf');

        $errorsOfChunk = null;
        $uploaded = false;

        $stream = new StreamIn('media', $this->workspace, null, [new Extension(['txt'])]);

        $stream
            ->afterValidate(static function (FileInterface $file, array $errors) use (&$errorsOfChunk): void {
                $errorsOfChunk = $errors;
            })
            ->beforeUpload(static function () use (&$uploaded): void {
                $uploaded = true;
            });

        try {
            $stream->process();
        } catch (ValidationFailedException) {
            // that is what this is about
        }

        $this->assertCount(1, (array) $errorsOfChunk);
        $this->assertFalse($uploaded, 'Nothing should have been written.');
    }

    public function testAnEventThatWasNotRegisteredIsNotCalled() : void
    {
        $this->fakeUpload('media', 'hello', 'greeting.txt');

        $stored = new StreamIn('media', $this->workspace)->process();

        $this->assertCount(1, $stored);
    }

    public function testItReportsHowMuchOfTheFileIsAssembled() : void
    {
        $stream = new StreamIn('media', $this->workspace);

        $_REQUEST['media'] = $this->dataUri('AAAA', 'text/plain', ['name' => 'file.bin', 'range' => '0-3']);
        $this->assertSame(4, $stream->process()[0]['assembled']);

        $_REQUEST['media'] = $this->dataUri('BBBB', 'text/plain', ['name' => 'file.bin', 'range' => '4-7']);
        $this->assertSame(8, $stream->process()[0]['assembled']);
    }

    /**
     * The whole point of `afterUpload`: it is where a run finds out that the file it
     * has been collecting is complete.
     */
    public function testTheAfterUploadEventSeesTheFileBeingCompleted() : void
    {
        $completed = [];
        $total = 8;

        foreach (['0-3' => 'AAAA', '4-7' => 'BBBB'] as $range => $chunk) {
            $_REQUEST['media'] = $this->dataUri($chunk, 'text/plain', [
                'name' => 'file.bin',
                'range' => $range,
                'size' => $total,
            ]);

            $onceWhole = static function (FileInterface $file, string $path, int $on) use (&$completed): void {
                if ($on === (int) $file->size) {
                    $completed[] = basename($path);
                }
            };

            new StreamIn('media', $this->workspace)->afterUpload($onceWhole)->process();
        }

        $this->assertSame(['file.bin'], $completed);
        $this->assertSame('AAAABBBB', file_get_contents($this->path('file.bin')));
    }

    public function testAValidationSeesTheChunkItIsGiven() : void
    {
        $this->fakeUpload('media', 'hello', 'greeting.txt');

        $seen = null;

        $validation = new class ($seen) implements ValidationInterface {
            public function __construct(private mixed &$seen)
            {
            }

            public function validate(FileInterface $file) : void
            {
                $this->seen = $file->name;
            }
        };

        new StreamIn('media', $this->workspace, null, [$validation])->process();

        $this->assertSame('greeting.txt', $seen);
    }
}

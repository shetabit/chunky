<?php

namespace Shetabit\Chunky\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use Shetabit\Chunky\Classes\Collector;
use Shetabit\Chunky\Tests\TestCase;
use Shetabit\Chunky\Traits\CollectBase64File;
use Shetabit\Chunky\Traits\CollectJsonFile;
use Shetabit\Chunky\Traits\CollectNormalFile;

#[CoversClass(Collector::class)]
#[CoversTrait(CollectBase64File::class)]
#[CoversTrait(CollectJsonFile::class)]
#[CoversTrait(CollectNormalFile::class)]
final class CollectorTest extends TestCase
{
    public function testItKeepsTheInputNameItWasGiven() : void
    {
        $collector = new Collector('media');

        $this->assertSame('media', $collector->getInputName());

        $this->assertSame($collector, $collector->setInputName('other'));
        $this->assertSame('other', $collector->getInputName());

        $this->assertNull(new Collector()->getInputName());
    }

    public function testItCollectsNothingWhenNothingWasSent() : void
    {
        $this->assertSame([], new Collector('media')->collect());
        $this->assertSame([], new Collector()->collect());
    }

    public function testItCollectsAnUploadedFile() : void
    {
        $this->fakeUpload('media', 'hello', 'greeting.txt');

        $files = new Collector('media')->collect();

        $this->assertCount(1, $files);
        $this->assertSame('hello', $files[0]->read());
        $this->assertSame('greeting.txt', $files[0]->name);
        $this->assertSame('application/octet-stream', $files[0]->mime);
        $this->assertSame(5, $files[0]->size);
    }

    public function testItCollectsSeveralUploadedFiles() : void
    {
        $first = $this->writeFile('first', 'one');
        $second = $this->writeFile('second', 'two');

        $_FILES['media'] = [
            'name' => ['1.txt', '2.txt'],
            'type' => ['text/plain', 'text/plain'],
            'tmp_name' => [$first, $second],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [3, 3],
        ];

        $files = new Collector('media')->collect();

        $this->assertCount(2, $files);
        $this->assertSame(['one', 'two'], $this->contentsOf($files));
        $this->assertSame(['1.txt', '2.txt'], $this->namesOf($files));
    }

    public function testItSkipsAnUploadThatFailed() : void
    {
        $this->fakeUpload('media', 'hello', 'greeting.txt', ['error' => UPLOAD_ERR_INI_SIZE]);

        $this->assertSame([], new Collector('media')->collect());
    }

    public function testItSkipsTheUploadsThatFailedAndKeepsTheOthers() : void
    {
        $_FILES['media'] = [
            'name' => ['1.txt', '2.txt'],
            'type' => ['text/plain', 'text/plain'],
            'tmp_name' => [$this->writeFile('first', 'one'), ''],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE],
            'size' => [3, 0],
        ];

        $files = new Collector('media')->collect();

        $this->assertCount(1, $files);
        $this->assertSame('one', $files[0]->read());
    }

    public function testAnUploadOfAnotherInputIsLeftAlone() : void
    {
        $this->fakeUpload('other', 'hello');

        $this->assertSame([], new Collector('media')->collect());
    }

    public function testItCollectsABase64DataUri() : void
    {
        $_REQUEST['media'] = $this->dataUri('hello', 'text/plain', ['name' => 'greeting.txt']);

        $files = new Collector('media')->collect();

        $this->assertCount(1, $files);
        $this->assertSame('hello', $files[0]->read());
        $this->assertSame('greeting.txt', $files[0]->name);
    }

    public function testItCollectsSeveralBase64DataUris() : void
    {
        $_REQUEST['media'] = [$this->dataUri('one'), $this->dataUri('two')];

        $files = new Collector('media')->collect();

        $this->assertCount(2, $files);
        $this->assertSame(['one', 'two'], $this->contentsOf($files));
    }

    public function testItCollectsAJsonPayload() : void
    {
        $_REQUEST['media'] = $this->jsonPayload('hello', 'text/plain', ['name' => 'greeting.txt']);

        $files = new Collector('media')->collect();

        $this->assertCount(1, $files);
        $this->assertSame('hello', $files[0]->read());
        $this->assertSame('greeting.txt', $files[0]->name);
    }

    public function testItCollectsSeveralJsonPayloads() : void
    {
        $_REQUEST['media'] = [$this->jsonPayload('one'), $this->jsonPayload('two')];

        $files = new Collector('media')->collect();

        $this->assertCount(2, $files);
        $this->assertSame(['one', 'two'], $this->contentsOf($files));
    }

    public function testItCollectsEveryFormatOfTheSameInputAtOnce() : void
    {
        $this->fakeUpload('media', 'uploaded');
        $_REQUEST['media'] = [$this->dataUri('base64'), $this->jsonPayload('json')];

        $files = new Collector('media')->collect();

        $this->assertCount(3, $files);
        $this->assertSame(['uploaded', 'base64', 'json'], $this->contentsOf($files));
    }

    /**
     * Request input that holds no file used to be collected all the same, and to
     * fall apart the moment something was read out of it.
     */
    public function testRequestInputThatHoldsNoFileIsLeftAlone() : void
    {
        $_REQUEST['media'] = ['hello', '42', 'null', '{"mime":"text/plain"}', ''];

        $this->assertSame([], new Collector('media')->collect());
    }

    public function testARequestValueThatIsNoStringIsLeftAlone() : void
    {
        $_REQUEST['media'] = [['nested' => 'array'], 42, null];

        $this->assertSame([], new Collector('media')->collect());
    }

    public function testADataUriIsNotCollectedAsAJsonPayloadAsWell() : void
    {
        $_REQUEST['media'] = $this->dataUri('hello');

        $this->assertCount(1, new Collector('media')->collect());
    }

    public function testItRecognizesTheFormatOfASingleValue() : void
    {
        $collector = new Collector('media');

        $this->assertTrue($collector->hasBase64Format($this->dataUri('hello')));
        $this->assertFalse($collector->hasBase64Format($this->jsonPayload('hello')));
        $this->assertFalse($collector->hasBase64Format(42));

        $this->assertTrue($collector->hasJsonFormat($this->jsonPayload('hello')));
        $this->assertFalse($collector->hasJsonFormat($this->dataUri('hello')));
        $this->assertFalse($collector->hasJsonFormat(42));
    }
}

<?php

namespace Shetabit\Chunky\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Shetabit\Chunky\Abstracts\TempFileAdapterAbstract;
use Shetabit\Chunky\Adapters\Base64ToTempFile;
use Shetabit\Chunky\Exceptions\InvalidDataException;
use Shetabit\Chunky\Tests\TestCase;

#[CoversClass(Base64ToTempFile::class)]
#[CoversClass(TempFileAdapterAbstract::class)]
final class Base64ToTempFileTest extends TestCase
{
    public function testItKeepsTheEncodedDataItWasGiven() : void
    {
        $adapter = new Base64ToTempFile('data:text/plain;base64,aGVsbG8=');

        $this->assertSame('data:text/plain;base64,aGVsbG8=', $adapter->getEncodedData());

        $this->assertSame($adapter, $adapter->setEncodedData('data:text/plain;base64,d29ybGQ='));
        $this->assertSame('data:text/plain;base64,d29ybGQ=', $adapter->getEncodedData());
    }

    public function testItDecodesTheMimeTypeAndTheData() : void
    {
        $decoded = new Base64ToTempFile('data:text/plain;base64,aGVsbG8=')->decodeData();

        $this->assertSame('text/plain', $decoded['mime']);
        $this->assertSame('hello', $decoded['data']);
    }

    public function testItDecodesBinaryData() : void
    {
        $binary = random_bytes(512);

        $decoded = new Base64ToTempFile($this->dataUri($binary))->decodeData();

        $this->assertSame($binary, $decoded['data']);
    }

    public function testTheSizeIsTheSizeOfWhatWasDecoded() : void
    {
        $decoded = new Base64ToTempFile($this->dataUri(str_repeat('a', 1000)))->decodeData();

        $this->assertSame(1000, $decoded['size']);
    }

    public function testItReadsTheMetaSectionOfTheDataUri() : void
    {
        $uri = $this->dataUri('hello', 'image/png', ['name' => 'avatar.png', 'range' => '0-1023']);

        $decoded = new Base64ToTempFile($uri)->decodeData();

        $this->assertSame('image/png', $decoded['mime']);
        $this->assertSame('avatar.png', $decoded['name']);
        $this->assertSame('0-1023', $decoded['range']);
        $this->assertSame('hello', $decoded['data']);
    }

    public function testASizeInTheMetaSectionWins() : void
    {
        $uri = $this->dataUri('hello', 'text/plain', ['size' => 4096]);

        $this->assertSame(4096, new Base64ToTempFile($uri)->decodeData()['size']);
    }

    public function testAMetaSectionThatHoldsNoNamedValueIsIgnored() : void
    {
        $decoded = new Base64ToTempFile('data:text/plain;-,aGVsbG8=')->decodeData();

        $this->assertSame('hello', $decoded['data']);
        $this->assertSame(5, $decoded['size']);
    }

    /**
     * A meta section can not smuggle a payload of its own past the one of the uri.
     */
    public function testTheMetaSectionCanNotReplaceTheDataOfTheUri() : void
    {
        $uri = $this->dataUri('hello', 'text/plain', ['data' => 'nonsense']);

        $this->assertSame('hello', new Base64ToTempFile($uri)->decodeData()['data']);
    }

    public function testADataUriWithoutAMimeTypeHasNoMimeType() : void
    {
        $decoded = new Base64ToTempFile('data:;base64,aGVsbG8=')->decodeData();

        $this->assertNull($decoded['mime']);
        $this->assertSame('hello', $decoded['data']);
    }

    #[DataProvider('payloadsThatAreNoDataUri')]
    public function testItRefusesAPayloadThatIsNoDataUri(string|null $payload) : void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('no base64 data uri');

        new Base64ToTempFile($payload)->decodeData();
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function payloadsThatAreNoDataUri() : array
    {
        return [
            'nothing at all' => [null],
            'an empty string' => [''],
            'plain text' => ['hello'],
            'json' => ['{"data":"aGVsbG8="}'],
            'a data uri without a payload' => ['data:text/plain;base64,'],
            'the payload alone' => ['aGVsbG8='],
        ];
    }

    public function testItRefusesAPayloadThatIsNoValidBase64() : void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('no valid base64');

        new Base64ToTempFile('data:text/plain;base64,!!!not base64!!!')->decodeData();
    }

    public function testItWritesTheDecodedDataIntoATemporaryFile() : void
    {
        $uri = $this->dataUri('hello world', 'text/plain', ['name' => 'greeting.txt']);

        $file = new Base64ToTempFile($uri)->toTempFile();

        $this->assertSame('hello world', $file->read());
        $this->assertSame('greeting.txt', $file->name);
        $this->assertSame('text/plain', $file->mime);
        $this->assertSame(11, $file->size);
    }

    public function testTheTemporaryFileDoesNotCarryTheRawDataAsAnAttribute() : void
    {
        $file = new Base64ToTempFile($this->dataUri('hello'))->toTempFile();

        $this->assertArrayNotHasKey('data', $file->getAttributes());
    }
}

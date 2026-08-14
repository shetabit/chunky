<?php

namespace Shetabit\Chunky\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Shetabit\Chunky\Abstracts\TempFileAdapterAbstract;
use Shetabit\Chunky\Adapters\JsonToTempFile;
use Shetabit\Chunky\Exceptions\InvalidDataException;
use Shetabit\Chunky\Tests\TestCase;

#[CoversClass(JsonToTempFile::class)]
#[CoversClass(TempFileAdapterAbstract::class)]
final class JsonToTempFileTest extends TestCase
{
    public function testItDecodesTheMimeTypeAndTheData() : void
    {
        $decoded = new JsonToTempFile($this->jsonPayload('hello', 'text/plain'))->decodeData();

        $this->assertSame('text/plain', $decoded['mime']);
        $this->assertSame('hello', $decoded['data']);
        $this->assertSame(5, $decoded['size']);
    }

    public function testItDecodesBinaryData() : void
    {
        $binary = random_bytes(512);

        $this->assertSame($binary, new JsonToTempFile($this->jsonPayload($binary))->decodeData()['data']);
    }

    public function testItReadsTheMetaSectionOfThePayload() : void
    {
        $payload = $this->jsonPayload('hello', 'image/png', ['name' => 'avatar.png', 'range' => '0-1023']);

        $decoded = new JsonToTempFile($payload)->decodeData();

        $this->assertSame('avatar.png', $decoded['name']);
        $this->assertSame('0-1023', $decoded['range']);
    }

    public function testASizeInTheMetaSectionWins() : void
    {
        $payload = $this->jsonPayload('hello', 'text/plain', ['size' => 4096]);

        $this->assertSame(4096, new JsonToTempFile($payload)->decodeData()['size']);
    }

    public function testAPayloadWithoutAMimeTypeHasNoMimeType() : void
    {
        $payload = (string) json_encode(['data' => base64_encode('hello')]);

        $this->assertNull(new JsonToTempFile($payload)->decodeData()['mime']);
    }

    public function testAMetaSectionThatIsNoObjectIsIgnored() : void
    {
        $payload = (string) json_encode(['meta' => 'nonsense', 'data' => base64_encode('hello')]);

        $decoded = new JsonToTempFile($payload)->decodeData();

        $this->assertSame('hello', $decoded['data']);
        $this->assertSame(5, $decoded['size']);
    }

    #[DataProvider('payloadsThatHoldNoFile')]
    public function testItRefusesAPayloadThatHoldsNoFile(string|null $payload) : void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('no json object holding a base64 `data` key');

        new JsonToTempFile($payload)->decodeData();
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function payloadsThatHoldNoFile() : array
    {
        return [
            'nothing at all' => [null],
            'an empty string' => [''],
            'no json' => ['hello'],
            'a number' => ['42'],
            'null' => ['null'],
            'an object without data' => ['{"mime":"text/plain"}'],
            'data that is no string' => ['{"data":42}'],
            'a data uri' => ['data:text/plain;base64,aGVsbG8='],
        ];
    }

    public function testItRefusesAPayloadWhoseDataIsNoValidBase64() : void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('no valid base64');

        new JsonToTempFile('{"data":"!!!not base64!!!"}')->decodeData();
    }

    public function testItWritesTheDecodedDataIntoATemporaryFile() : void
    {
        $payload = $this->jsonPayload('hello world', 'text/plain', ['name' => 'greeting.txt']);

        $file = new JsonToTempFile($payload)->toTempFile();

        $this->assertSame('hello world', $file->read());
        $this->assertSame('greeting.txt', $file->name);
        $this->assertSame('text/plain', $file->mime);
        $this->assertSame(11, $file->size);
    }
}

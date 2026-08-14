<?php

namespace Shetabit\Chunky\Adapters;

use Shetabit\Chunky\Abstracts\TempFileAdapterAbstract;
use Shetabit\Chunky\Exceptions\InvalidDataException;

/**
 * Reads a file out of a base64 data uri.
 *
 * @example data:image/png;name=avatar.png;range=0-1023;base64,iVBORw0KGgo...
 */
class Base64ToTempFile extends TempFileAdapterAbstract
{
    /**
     * Matches a data uri: its mime type, its meta section and its payload.
     */
    public const string PATTERN = '/^data:((?:\w+\/(?:(?!;).)+)?)((?:;[\w\W]*?[^;])*),(.+)$/iu';

    /**
     * Matches a single `;name=value` (or `;name:value`, or `;name`) of the meta section.
     */
    protected const string META_PATTERN = '/;(\w+)[=:]?([\w.\-]*)/iu';

    /**
     * Decode the data uri into the content of the file and its attributes.
     *
     * @return array{data: string, ...<string, mixed>}
     */
    public function decodeData() : array
    {
        $encodedData = $this->getEncodedData();

        if ($encodedData === null || preg_match(self::PATTERN, $encodedData, $matches) !== 1) {
            throw new InvalidDataException('The given data is no base64 data uri.');
        }

        $data = base64_decode($matches[3], true);

        if ($data === false) {
            throw new InvalidDataException('The payload of the given data uri is no valid base64.');
        }

        $meta = $matches[2] === '' ? [] : $this->extractMetas($matches[2]);

        // The size of the file is the size of what was decoded, unless the meta
        // section named one itself.
        $meta['size'] = isset($meta['size']) ? (int) $meta['size'] : strlen($data);

        // The payload and the mime type of the uri itself come last, so that a meta
        // section carrying a `data` or a `mime` of its own can not replace them.
        return array_merge($meta, ['mime' => $matches[1] === '' ? null : $matches[1], 'data' => $data]);
    }

    /**
     * Read the `;name=value` pairs of the meta section of a data uri.
     *
     * @return array<string, string>
     */
    protected function extractMetas(string $meta) : array
    {
        if (preg_match_all(self::META_PATTERN, $meta, $matches) === 0) {
            return [];
        }

        return array_combine($matches[1], $matches[2]);
    }
}

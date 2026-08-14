<?php

namespace Shetabit\Chunky\Adapters;

use Shetabit\Chunky\Abstracts\TempFileAdapterAbstract;
use Shetabit\Chunky\Exceptions\InvalidDataException;

/**
 * Reads a file out of a json payload.
 *
 * @example {"mime":"image/png","meta":{"name":"avatar.png","range":"0-1023"},"data":"iVBORw0KGgo..."}
 */
class JsonToTempFile extends TempFileAdapterAbstract
{
    /**
     * Decode the json payload into the content of the file and its attributes.
     *
     * @return array{data: string, ...<string, mixed>}
     */
    public function decodeData() : array
    {
        $encodedData = $this->getEncodedData();

        // The payload is decoded into an array: as an object, a `meta` section would
        // be an object as well, and reading a key off of it is a fatal error.
        $decoded = $encodedData === null ? null : json_decode($encodedData, true);

        if (! is_array($decoded) || ! isset($decoded['data']) || ! is_string($decoded['data'])) {
            throw new InvalidDataException('The given data is no json object holding a base64 `data` key.');
        }

        $data = base64_decode($decoded['data'], true);

        if ($data === false) {
            throw new InvalidDataException('The `data` key of the given json is no valid base64.');
        }

        $meta = isset($decoded['meta']) && is_array($decoded['meta'])
            ? array_filter($decoded['meta'], is_string(...), ARRAY_FILTER_USE_KEY)
            : [];

        // The size of the file is the size of what was decoded, unless the meta
        // section named one itself.
        $meta['size'] = isset($meta['size']) ? (int) $meta['size'] : strlen($data);

        $mime = isset($decoded['mime']) && is_string($decoded['mime']) ? $decoded['mime'] : null;

        // The payload and the mime type of the payload itself come last, so that a
        // meta section carrying a `data` or a `mime` of its own can not replace them.
        return array_merge($meta, ['mime' => $mime, 'data' => $data]);
    }
}

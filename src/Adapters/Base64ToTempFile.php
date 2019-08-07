<?php

namespace Shetabit\Chunky\Adapters;

use Shetabit\Chunky\Abstracts\TempFileAdapterAbstract;
use Shetabit\Chunky\Contracts\TempFileAdapterInterface;

class Base64ToTempFile extends TempFileAdapterAbstract implements TempFileAdapterInterface
{
    public function decodeData()
    {
        $encodedData = $this->getEncodedData();
        $pattern = '/^data:((?:\w+\/(?:(?!;).)+)?)((?:;[\w\W]*?[^;])*),(.+)$/iu';

        $foundFlag = (bool) preg_match($pattern, $encodedData, $matches);

        $mime = $matches[1];
        $meta = $matches[2] ? $this->extractMetas($matches[2]) : [];
        $data = base64_decode($matches[3]);

        $meta['size'] = $meta['size'] ?? intval(mb_strlen($data) * (4/3) -1); // add file size if not exists

        return $foundFlag ? array_merge(['mime' => $mime, 'data' => $data], $meta) : null;
    }

    private function extractMetas($meta)
    {
        $pattern = '/\;(\w+)[=:]?([\w\.]*)/iu';
        $foundFlag = preg_match_all($pattern,$meta,$matches);

        return $foundFlag ? array_combine($matches[1], $matches[2]) : [];
    }
}

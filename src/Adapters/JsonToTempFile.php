<?php

namespace Shetabit\Chunky\Adapters;

use Shetabit\Chunky\Abstracts\TempFileAdapterAbstract;
use Shetabit\Chunky\Contracts\TempFileAdapterInterface;

class JsonToTempFile extends TempFileAdapterAbstract implements TempFileAdapterInterface
{
    public function decodeData()
    {
        $jsonObj = json_decode($this->getEncodedData(), false);

        $foundFlag = !empty($jsonObj);

        $mime = $jsonObj->mime ?? null;
        $meta = $jsonObj->meta ?? [];
        $meta['size'] = $meta['size'] ?? intval(mb_strlen($jsonObj->data) * 3 / 4 - 1); // add data size

        $data = base64_decode($jsonObj->data);

        return $foundFlag ? array_merge(['mime' => $mime, 'data' => $data], $meta) : null;
    }
}

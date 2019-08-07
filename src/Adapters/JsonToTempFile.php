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
        $data = base64_decode($jsonObj->data);

        $meta['size'] = $meta['size'] ?? intval(mb_strlen($data) * (4/3) -1); // add file size if not exists

        return $foundFlag ? array_merge(['mime' => $mime, 'data' => $data], $meta) : null;
    }
}

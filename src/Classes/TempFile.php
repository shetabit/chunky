<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\TempFileInterface;

class TempFile extends File implements TempFileInterface
{
    /**
     * Create (or overwrite) new temp file
     *
     * @param $data
     * @return $this
     */
    public function create($data)
    {
        $handler = $this->openTemporary();

        fwrite($handler, $data);

        return $this;
    }

    /**
     * Store file in the given path
     *
     * @param $path
     * @param null $offset
     * @param null $length
     * @return $this
     */
    public function saveAs($path, $offset = null, $length = null)
    {
        $resource = $this->read($offset, $length);

        $destinationFile = new File($path);

        $destinationFile->write($resource, $offset);

        return $this;
    }

    /**
     * Open a file in temporary mode.
     * temporary files will be removed automatically as the script ends
     *
     * @return bool|resource
     */
    protected function openTemporary()
    {
        $handler = tmpfile();

        $meta = stream_get_meta_data($handler);
        $this->setPath($meta['uri']);

        array_push($this->handlers, $handler);

        return $handler;
    }
}

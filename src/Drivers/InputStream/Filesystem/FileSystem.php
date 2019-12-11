<?php

namespace Shetabit\Chunky\Drivers\InputStream;

use Shetabit\Chunky\Abstracts\InputStreamDriverAbstract;
use Shetabit\Chunky\Contracts\InputStreamDriverInterface;
use Shetabit\Chunky\Models\Chunk;
use Shetabit\Chunky\Models\File;

class FileSystem extends InputStreamDriverAbstract implements InputStreamDriverInterface
{
    protected $config;

    public function __construct($config)
    {
        $this->config = (object) $config;
    }

    /**
     * Retrieve stream status
     *
     * @param string $fileId
     *
     * @return array
     */
    public function status(string $fileId) : array
    {
        $file = File::where('id', '=', $fileId)->with('chunks')->first();

        $fullName = $file->client_name.'.'.$file->client_extension;

        $start = 0;
        if ($this->config->type == 'monolithic') {
            if (file_exists($file->path.$file->name.'.'.$file->extension)) {
                $start = filesize($file->path);
            }
        } else {
            if ($file->chunks->isNotEmpty()) {
                $latestChunk = $file->chunks->latest();
                $start = $latestChunk->offset + $latestChunk->size;
            }
        }

        return [
            'id' => $file->id,
            'full_name' => $fullName,
            'size' => $file->size,
            'start' => $start,
            'chunk_size' => $this->chunk_size,
            'completed' => $file->hasCompleted(),
        ];
    }

    /**
     * Retrieve and store chunks
     *
     * @param callable|null $decoder
     *
     * @return Chunk
     */
    public function store(callable $decoder = null) : Chunk
    {
        $chunk = parent::store($decoder);
        $file = $chunk->file;

        $hasNextChunk = $chunk->offset < $file->size && $file->hasNotCompleted();
        $end = $chunk->offset + $chunk->size;

        if ($hasNextChunk) {
            if ($this->config->type == 'monolithic') {
                $this->storeMonolithic($chunk);
            } else {
                $this->storePolylithic($chunk);
            }
        }

        if ($end == $file->size) {
            $file->markAsCompleted();

            if ($this->config->type == 'polylithic') {
                $this->mergePolylithicChunks($file);
            }
        }

        return $chunk;
    }

    public function storeMonolithic(Chunk $chunk)
    {
        $file = $chunk->file;
        $fullPath = $file->storage_path.$file->storage_name;

        $handle = fopen($fullPath, 'a+');

        fseek($handle, $chunk->offset);

        flock($handle, LOCK_EX | LOCK_NB);

        fwrite($handle, $chunk->data, $chunk->size);

        flock($handle, LOCK_UN);

        fclose($handle);
    }

    public function storePolylithic(Chunk $chunk)
    {
        $file = $chunk->file;
        $fullPath = $file->storage_path.$chunk->id;

        $handle = fopen($fullPath, 'w');

        fwrite($handle, $chunk->data, $chunk->size);

        fclose($handle);
    }

    public function mergePolylithicChunks(File $file)
    {
        $chunks = $file->chunks()->orderBy('offset','ASC');
        $fullPath = $file->storage_path.$file->storage_name;
        $handle = fopen($fullPath, 'w');

        foreach($chunks as $chunk)
        {
            $chunkFullPath = $file->storage_path.$chunk->id;
            $chunkHandle = fopen($chunkFullPath, 'r');

            $data = fread($chunkHandle, filesize($chunkFullPath));

            fseek($handle, $chunk->offset);
            fwrite($handle, $data, $chunk->size);

            fclose($chunkFullPath);
            unlink($chunkFullPath);
        }

        fclose($handle);
    }
}

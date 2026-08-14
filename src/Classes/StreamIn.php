<?php

namespace Shetabit\Chunky\Classes;

use Shetabit\Chunky\Contracts\StreamInterface;
use Shetabit\Chunky\Contracts\TempFileInterface;
use Shetabit\Chunky\Exceptions\FileNotFoundException;

class StreamIn implements StreamInterface
{
    /**
     * What separates the two ends of a range: `0-1023`.
     */
    public const string RANGE_DELIMITER = '-';

    /**
     * Collects the chunks that were sent.
     */
    protected Collector $collector;

    /**
     * The chunks of the last run.
     *
     * @var list<TempFileInterface>
     */
    protected array $files = [];

    /**
     * StreamIn constructor.
     *
     * @param string         $inputName the name of the html input the chunks are sent under
     * @param string         $path      the directory the chunks are assembled in
     * @param Collector|null $collector collects the chunks, the one of the input's name by default
     */
    public function __construct(
        protected string $inputName,
        protected string $path,
        Collector|null $collector = null,
    ) {
        $this->collector = $collector ?? new Collector($inputName);
    }

    /**
     * Store every chunk that was sent at its place in the file it belongs to.
     *
     * @return list<array{path: string, range: array{int, int|null}, file: TempFileInterface}>
     */
    public function process() : array
    {
        if (! is_dir($this->path)) {
            throw new FileNotFoundException(sprintf('The upload directory "%s" does not exist.', $this->path));
        }

        $storedFiles = [];

        $this->files = $this->collector->collect();

        foreach ($this->files as $file) {
            $name = $this->getName($file);

            if ($name === null) { // a chunk without a name has no file to belong to
                continue;
            }

            $range = $this->getRange($file);
            $path = $this->path.DIRECTORY_SEPARATOR.$name;

            $file->saveAs($path, ...$range);

            $storedFiles[] = [
                'path' => $path,
                'range' => $range,
                'file' => $file,
            ];
        }

        return $storedFiles;
    }

    /**
     * The chunks of the last run.
     *
     * @return list<TempFileInterface>
     */
    public function getFiles() : array
    {
        return $this->files;
    }

    /**
     * The directory the chunks are assembled in.
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * The name the given chunk is stored under.
     *
     * The name is whatever the client sent, so only the file name of it is used: a
     * name like `../../config.php` would otherwise write outside of the directory
     * the stream was pointed at.
     */
    public function getName(TempFileInterface $file) : string|null
    {
        $name = $file->name;

        if (! is_string($name)) {
            return null;
        }

        $name = basename(str_replace('\\', '/', trim($name)));

        return in_array($name, ['', '.', '..'], true) ? null : $name;
    }

    /**
     * Where the given chunk belongs in the file that is being assembled.
     *
     * The range of a chunk (`1024-2047`) names the bytes of the whole file it holds,
     * so it becomes the offset the chunk is written at and the number of bytes that
     * are taken from it. A chunk without a range is a whole file and goes to offset 0.
     *
     * @return array{int, int|null}
     */
    public function getRange(TempFileInterface $file) : array
    {
        $size = is_numeric($file->size) ? (int) $file->size : null;

        if (! is_string($file->range) || $file->range === '') {
            return [0, $size];
        }

        $parts = explode(self::RANGE_DELIMITER, $file->range);

        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return [0, $size];
        }

        $start = (int) $parts[0];
        $end = (int) $parts[1];

        if ($start < 0 || $end < $start) { // a range that makes no sense is no range
            return [0, $size];
        }

        return [$start, $end - $start + 1];
    }
}

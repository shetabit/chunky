<?php

namespace Shetabit\Chunky\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Shetabit\Chunky\Contracts\FileInterface;

abstract class TestCase extends BaseTestCase
{
    protected string $workspace;

    protected function setUp() : void
    {
        parent::setUp();

        $workspace = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('chunky-test-', true);

        mkdir($workspace, 0o777, true);

        $this->workspace = $workspace;

        $_FILES = [];
        $_REQUEST = [];
        $_POST = [];
        $_GET = [];
    }

    protected function tearDown() : void
    {
        $this->removeDirectory($this->workspace);

        $_FILES = [];
        $_REQUEST = [];

        parent::tearDown();
    }

    protected function path(string $name = '') : string
    {
        return $name === '' ? $this->workspace : $this->workspace.DIRECTORY_SEPARATOR.$name;
    }

    protected function writeFile(string $name, string $contents) : string
    {
        $path = $this->path($name);

        file_put_contents($path, $contents);

        return $path;
    }

    /**
     * Fill `$_FILES` the way PHP does for a single upload.
     *
     * @param array<string, mixed> $overrides
     */
    protected function fakeUpload(
        string $inputName,
        string $contents,
        string $name = 'chunk.bin',
        array $overrides = [],
    ) : string {
        $path = $this->writeFile(uniqid('upload-', true), $contents);

        $_FILES[$inputName] = array_merge([
            'name' => $name,
            'type' => 'application/octet-stream',
            'tmp_name' => $path,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($contents),
        ], $overrides);

        return $path;
    }

    /**
     * Build a base64 data uri out of the given data and meta information.
     *
     * @param array<string, string|int> $meta
     */
    protected function dataUri(string $data, string $mime = 'application/octet-stream', array $meta = []) : string
    {
        $metaSection = '';

        foreach ($meta as $key => $value) {
            $metaSection .= ';'.$key.'='.$value;
        }

        return sprintf('data:%s%s;base64,%s', $mime, $metaSection, base64_encode($data));
    }

    /**
     * Build a json payload out of the given data and meta information.
     *
     * @param array<string, string|int> $meta
     */
    protected function jsonPayload(
        string $data,
        string|null $mime = 'application/octet-stream',
        array $meta = [],
    ) : string {
        return (string) json_encode([
            'mime' => $mime,
            'meta' => $meta,
            'data' => base64_encode($data),
        ]);
    }

    /**
     * What the given files hold.
     *
     * @param list<FileInterface> $files
     *
     * @return list<string>
     */
    protected function contentsOf(array $files) : array
    {
        return array_map(static fn (FileInterface $file): string => $file->read(), $files);
    }

    /**
     * The name attribute of the given files.
     *
     * @param list<FileInterface> $files
     *
     * @return list<mixed>
     */
    protected function namesOf(array $files) : array
    {
        return array_map(static fn (FileInterface $file): mixed => $file->name, $files);
    }

    protected function removeDirectory(string $directory) : void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;

            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}

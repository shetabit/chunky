<?php

/**
 * Takes a chunk of a file and writes it where it belongs.
 *
 * This is the whole server side of a resumable, chunked upload: a collector picks the
 * chunk out of the request and `StreamIn` writes it at the offset of its range. A
 * chunk that arrives twice overwrites itself, and chunks that arrive out of order end
 * up in the right place all the same.
 */

require dirname(__DIR__).'/bootstrap.php';

use Shetabit\Chunky\Classes\ChunkedUploadCollector;
use Shetabit\Chunky\Classes\StreamIn;
use Shetabit\Chunky\Contracts\TempFileInterface;
use Shetabit\Chunky\Exceptions\ChunkyException;
use Shetabit\Chunky\Exceptions\ValidationFailedException;
use Shetabit\Chunky\Validations\Size;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Send a chunk with POST.'], 405);

    exit;
}

$completed = [];

try {
    $stream = new StreamIn(INPUT_NAME, UPLOAD_PATH, new ChunkedUploadCollector(INPUT_NAME), [
        // A file is turned away at its first chunk, by the size the client announced
        // for the whole of it — no need to take 200 megabytes to find out.
        //
        // `Extension` and `MimeType` sit next to this one; they are left out here so
        // that the example takes whatever you drop on it.
        new Size('200M'),
    ]);

    // Where an application does the interesting part: the last chunk of a file has
    // arrived, and the file is whole.
    $stream->afterUpload(static function (TempFileInterface $file, string $path, int $assembled) use (&$completed): void {
        if (is_numeric($file->size) && $assembled >= (int) $file->size) {
            $completed[] = basename($path);
        }
    });

    $stored = $stream->process();

    if ($stored === []) {
        json(['error' => 'The request carried no file.'], 422);

        exit;
    }

    $chunks = [];

    foreach ($stored as $chunk) {
        [$offset, $length] = $chunk['range'];

        $chunks[] = [
            'name' => basename($chunk['path']),
            'offset' => $offset,
            'length' => $length,
            'mime' => $chunk['file']->mime,
            // How much of the file is on disk now — where a client that stops here
            // has to pick the upload up again.
            'uploaded' => $chunk['assembled'],
        ];
    }

    json(['chunks' => $chunks, 'completed' => $completed]);
} catch (ValidationFailedException $exception) {
    json(['error' => $exception->getMessage(), 'errors' => $exception->getErrors()], 422);
} catch (ChunkyException $exception) {
    json(['error' => $exception->getMessage()], 400);
}

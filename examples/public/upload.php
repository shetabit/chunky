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

use Chunky\Example\RangeAwareCollector;
use Shetabit\Chunky\Classes\StreamIn;
use Shetabit\Chunky\Exceptions\ChunkyException;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Send a chunk with POST.'], 405);

    exit;
}

try {
    $stream = new StreamIn(INPUT_NAME, UPLOAD_PATH, new RangeAwareCollector(INPUT_NAME));

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
            'uploaded' => (int) (@filesize($chunk['path']) ?: 0),
        ];
    }

    json(['chunks' => $chunks]);
} catch (ChunkyException $exception) {
    json(['error' => $exception->getMessage()], 400);
}

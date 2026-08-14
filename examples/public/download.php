<?php

require dirname(__DIR__).'/bootstrap.php';

use Shetabit\Chunky\Classes\StreamOut;
use Shetabit\Chunky\Exceptions\ChunkyException;

$path = uploaded_file_path($_GET['name'] ?? null);

if ($path === null) {
    json(['error' => 'Name a file to download.'], 400);

    exit;
}

// The stream keeps going until the whole file has reached the client.
set_time_limit(0);

try {
    // The second argument throttles the stream — 20 milliseconds after every buffer,
    // so that a download of a small file is slow enough to watch, and to pause.
    $stream = new StreamOut($path, delay: 20_000, bufferSize: 64 * 1024);

    $stream->process();
} catch (ChunkyException $exception) {
    json(['error' => $exception->getMessage()], 404);
}

<?php

require dirname(__DIR__).'/bootstrap.php';

$files = [];

foreach (scandir(UPLOAD_PATH) ?: [] as $entry) {
    $path = UPLOAD_PATH.DIRECTORY_SEPARATOR.$entry;

    if ($entry[0] === '.' || ! is_file($path)) {
        continue;
    }

    $files[] = [
        'name' => $entry,
        'size' => filesize($path),
        'mime' => mime_content_type($path) ?: 'application/octet-stream',
        'modified' => date(DATE_ATOM, (int) filemtime($path)),
        'url' => 'download.php?name='.rawurlencode($entry),
    ];
}

usort($files, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

json(['files' => $files]);

<?php

require __DIR__.'/vendor/autoload.php';

const UPLOAD_PATH = __DIR__.'/uploads';

const INPUT_NAME = 'media';

if (! is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0o777, true);
}

function uploaded_file_path(mixed $name) : string|null
{
    if (! is_string($name)) {
        return null;
    }

    $name = basename(str_replace('\\', '/', trim($name)));

    if (in_array($name, ['', '.', '..'], true)) {
        return null;
    }

    return UPLOAD_PATH.DIRECTORY_SEPARATOR.$name;
}

function json(mixed $data, int $status = 200) : void
{
    http_response_code($status);
    header('Content-Type: application/json');

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

<?php

/**
 * What every page and every endpoint of the example starts with.
 *
 * The example is an application of its own: it has its own composer.json and installs
 * shetabit/chunky the way any other application would.
 */

require __DIR__.'/vendor/autoload.php';

/**
 * Where the uploaded files end up.
 */
const UPLOAD_PATH = __DIR__.'/uploads';

/**
 * The name of the html input the files are sent under.
 */
const INPUT_NAME = 'media';

if (! is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0o777, true);
}

/**
 * The path of an uploaded file, or `null` when the name points somewhere else.
 *
 * The example hands files back out by name, so a name that leaves the upload
 * directory would hand out — or delete — whatever it points at.
 */
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

/**
 * Answer the request with json.
 */
function json(mixed $data, int $status = 200) : void
{
    http_response_code($status);
    header('Content-Type: application/json');

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

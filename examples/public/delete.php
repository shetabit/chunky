<?php

/**
 * Throws an uploaded file away, so that the example can be run again from the top.
 */

require dirname(__DIR__).'/bootstrap.php';

use Shetabit\Chunky\Classes\File;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Delete with POST.'], 405);

    exit;
}

$path = uploaded_file_path($_POST['name'] ?? null);

if ($path === null) {
    json(['error' => 'Name a file to delete.'], 400);

    exit;
}

new File($path)->remove();

json(['deleted' => basename($path)]);

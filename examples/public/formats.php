<?php

/**
 * The three formats a file can be sent in, side by side.
 *
 * The page below sends the same file three times — as a plain upload, as a base64
 * data uri and as a json payload — and the collector picks all three of them out of
 * the one request, because that is what it does.
 *
 * A data uri and a json payload carry their own name, range and size in their meta
 * section, so they need no field of their own next to them, which is what makes them
 * worth the third of extra bytes base64 costs.
 */

require dirname(__DIR__).'/bootstrap.php';

use Shetabit\Chunky\Classes\Collector;
use Shetabit\Chunky\Exceptions\ChunkyException;

$collected = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach (new Collector(INPUT_NAME)->collect() as $file) {
            $collected[] = [
                'name' => $file->name,
                'mime' => $file->mime,
                'size' => $file->size,
                'range' => $file->range,
                'read from the temporary file' => $file->read(0, 60),
            ];
        }
    } catch (ChunkyException $exception) {
        $error = $exception->getMessage();
    }
}

$text = 'Chunky reads uploads, data uris and json payloads.';

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chunky &mdash; the three formats</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main>
    <h1>The three formats</h1>

    <p class="lead">
        The same text is sent three times in one request &mdash; as a plain upload, as a base64 data uri and as a
        json payload &mdash; and <code>Collector</code> picks all three of them out of it.
    </p>

    <p><a href="index.php">&larr; back to the chunked upload</a></p>

    <?php if ($error !== null) { ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php } ?>

    <?php if ($collected !== []) { ?>
        <h2><?= count($collected) ?> files were collected</h2>
        <pre><?= htmlspecialchars(json_encode($collected, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '') ?></pre>
    <?php } ?>

    <form method="post" enctype="multipart/form-data" id="formats">
        <p>
            <label for="upload">A file to send as a plain upload (optional, a text file is made up otherwise):</label>
            <input id="upload" type="file" name="<?= INPUT_NAME ?>[]">
        </p>

        <!-- A base64 data uri: `;name=`, `;range=` and `;size=` become attributes of the file. -->
        <input type="hidden" name="<?= INPUT_NAME ?>[]" value="data:text/plain;name=from-a-data-uri.txt;range=0-<?= strlen($text) - 1 ?>;base64,<?= base64_encode($text) ?>">

        <!-- A json payload: the same, with the attributes under `meta`. -->
        <input type="hidden" name="<?= INPUT_NAME ?>[]" value='<?= htmlspecialchars(json_encode([
            'mime' => 'text/plain',
            'meta' => ['name' => 'from-a-json-payload.txt', 'range' => '0-'.(strlen($text) - 1)],
            'data' => base64_encode($text),
        ]) ?: '', ENT_QUOTES) ?>'>

        <button type="submit">send all three</button>
    </form>

    <script>
        // A made up file, so that the plain upload has something to send when no file
        // was picked.
        document.getElementById('formats').addEventListener('submit', (event) => {
            const input = document.getElementById('upload');

            if (input.files.length > 0) {
                return;
            }

            const file = new File([<?= json_encode($text) ?>], 'from-a-plain-upload.txt', {type: 'text/plain'});
            const transfer = new DataTransfer();

            transfer.items.add(file);
            input.files = transfer.files;
        });
    </script>
</main>
</body>
</html>

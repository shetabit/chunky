<?php

/**
 * The page of the example.
 *
 * Pick a file, watch it go up chunk by chunk, pause it in the middle, reload the page
 * and pick it up where it stopped. The list underneath hands the files back out again
 * through a resumable download.
 */

require dirname(__DIR__).'/bootstrap.php';

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chunky &mdash; resumable chunked upload</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main>
    <h1>Chunky</h1>

    <p class="lead">
        A file goes up in pieces of <span id="chunk-size-label"></span>, one request per piece. Pause it, reload the
        page, pull the network cable &mdash; it picks up where it stopped, because every chunk says which bytes of the
        file it holds and the server writes it exactly there.
    </p>

    <section class="panel">
        <h2>Upload</h2>

        <div id="drop" class="drop">
            <input id="file" type="file" hidden>
            <button type="button" id="pick">choose a file</button>
            <span class="hint">or drop one here</span>
        </div>

        <div id="progress" hidden>
            <p class="filename"><strong id="upload-name"></strong> <span id="upload-detail" class="muted"></span></p>

            <div class="bar"><div id="bar-fill"></div></div>

            <p class="controls">
                <button type="button" id="pause">pause</button>
                <button type="button" id="resume" hidden>resume</button>
                <span id="status" class="muted"></span>
            </p>
        </div>

        <p id="error" class="error" hidden></p>

        <p class="settings">
            <label for="chunk-size">chunk size</label>
            <select id="chunk-size">
                <option value="65536">64 KB</option>
                <option value="262144" selected>256 KB</option>
                <option value="1048576">1 MB</option>
                <option value="5242880">5 MB</option>
            </select>
        </p>
    </section>

    <section class="panel">
        <h2>Uploaded files</h2>

        <p class="muted">
            The links below are served by <code>StreamOut</code>, which answers the <code>Range</code> header, so a
            download manager can take them in pieces and resume them too.
        </p>

        <table id="files">
            <thead>
                <tr><th>name</th><th>size</th><th>type</th><th></th></tr>
            </thead>
            <tbody></tbody>
        </table>

        <p id="no-files" class="muted">Nothing has been uploaded yet.</p>
    </section>

    <p class="footer">
        <a href="formats.php">the three formats a file can be sent in &rarr;</a>
    </p>
</main>

<script src="assets/app.js"></script>
</body>
</html>

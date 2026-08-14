/**
 * The client side of the example: it cuts a file into chunks and sends them one at a
 * time, telling the server which bytes of the file every chunk holds.
 *
 * There is no library behind this on purpose — the whole protocol is the `range`
 * field of the requests below.
 */

const el = (id) => document.getElementById(id);

const state = {
    file: null,
    offset: 0,
    chunkSize: Number(el('chunk-size').value),
    paused: false,
    running: false,
};

/** Bytes as something a human reads. */
function humanSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unit = 0;

    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit++;
    }

    return `${unit === 0 ? size : size.toFixed(1)} ${units[unit]}`;
}

function showError(message) {
    el('error').textContent = message;
    el('error').hidden = message === '';
}

function renderProgress() {
    const percent = state.file.size === 0 ? 100 : Math.round((state.offset / state.file.size) * 100);

    el('bar-fill').style.width = `${percent}%`;
    el('upload-detail').textContent = `${humanSize(state.offset)} of ${humanSize(state.file.size)} — ${percent}%`;
}

/**
 * How much of this file is already on the server, so that an upload that was
 * interrupted — by a pause, a reload or a broken connection — goes on from there.
 */
async function uploadedSoFar(name) {
    const response = await fetch('files.php');
    const {files} = await response.json();
    const found = files.find((file) => file.name === name);

    return found === undefined ? 0 : found.size;
}

/** Send one chunk, and answer with how much of the file is on the server now. */
async function sendChunk(file, start) {
    const end = Math.min(start + state.chunkSize, file.size);
    const body = new FormData();

    // The chunk itself, under the name of the html input, with the name of the whole
    // file — that is what the server stores it as.
    body.append('media', file.slice(start, end), file.name);

    // Which bytes of the whole file this chunk holds. The server writes it there.
    body.append('range', `${start}-${end - 1}`);

    // How big the whole file is, so that one too large can be turned away at its
    // first chunk instead of after its last.
    body.append('size', String(file.size));

    const response = await fetch('upload.php', {method: 'POST', body});
    const result = await response.json();

    if (!response.ok) {
        throw new Error(result.error ?? `The server answered with ${response.status}.`);
    }

    return result.chunks[0].uploaded;
}

async function run() {
    if (state.running) {
        return;
    }

    state.running = true;
    state.paused = false;

    el('pause').hidden = false;
    el('resume').hidden = true;
    showError('');

    try {
        while (state.offset < state.file.size) {
            if (state.paused) {
                el('status').textContent = 'paused';

                return;
            }

            el('status').textContent = 'uploading…';

            state.offset = await sendChunk(state.file, state.offset);

            renderProgress();
        }

        el('status').textContent = 'done';
        el('pause').hidden = true;

        await renderFiles();
    } catch (error) {
        showError(error.message);
        el('status').textContent = 'stopped';
        el('pause').hidden = true;
        el('resume').hidden = false;
    } finally {
        state.running = false;
    }
}

async function start(file) {
    state.file = file;
    state.chunkSize = Number(el('chunk-size').value);

    el('progress').hidden = false;
    el('upload-name').textContent = file.name;

    // A file of this name that is already half up here is continued, not sent again.
    state.offset = Math.min(await uploadedSoFar(file.name), file.size);

    renderProgress();

    if (state.offset > 0) {
        el('status').textContent = `resuming at ${humanSize(state.offset)}`;
    }

    await run();
}

async function renderFiles() {
    const response = await fetch('files.php');
    const {files} = await response.json();
    const body = document.querySelector('#files tbody');

    body.textContent = '';
    el('no-files').hidden = files.length > 0;
    el('files').hidden = files.length === 0;

    for (const file of files) {
        const row = body.insertRow();

        row.insertCell().append(file.name);
        row.insertCell().append(humanSize(file.size));
        row.insertCell().append(file.mime);

        const link = document.createElement('a');

        link.href = file.url;
        link.textContent = 'download';
        link.setAttribute('download', file.name);

        const remove = document.createElement('button');

        remove.type = 'button';
        remove.className = 'link';
        remove.textContent = 'delete';
        remove.addEventListener('click', async () => {
            const form = new FormData();

            form.append('name', file.name);

            await fetch('delete.php', {method: 'POST', body: form});
            await renderFiles();
        });

        const actions = row.insertCell();

        actions.append(link, ' · ', remove);
    }
}

el('pick').addEventListener('click', () => el('file').click());

el('file').addEventListener('change', (event) => {
    if (event.target.files.length > 0) {
        start(event.target.files[0]);
    }
});

el('pause').addEventListener('click', () => {
    state.paused = true;
    el('pause').hidden = true;
    el('resume').hidden = false;
});

el('resume').addEventListener('click', () => run());

el('chunk-size').addEventListener('change', () => {
    state.chunkSize = Number(el('chunk-size').value);
    el('chunk-size-label').textContent = humanSize(state.chunkSize);
});

const drop = el('drop');

for (const name of ['dragenter', 'dragover', 'dragleave', 'drop']) {
    drop.addEventListener(name, (event) => {
        event.preventDefault();
        drop.classList.toggle('over', name === 'dragenter' || name === 'dragover');
    });
}

drop.addEventListener('drop', (event) => {
    if (event.dataTransfer.files.length > 0) {
        start(event.dataTransfer.files[0]);
    }
});

el('chunk-size-label').textContent = humanSize(state.chunkSize);

renderFiles();

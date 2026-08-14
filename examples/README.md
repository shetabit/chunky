# The chunky example

A small application that uses [shetabit/chunky](https://github.com/shetabit/chunky) to upload a file chunk by chunk
and to hand it back out as a resumable download.

It is an application of its own, not a part of the package: it has its own `composer.json`, its own dependencies and
its own container, and it uses chunky the way any other application would. Nothing in here is needed to use the
package, and nothing in here is installed with it.

## Run it

```bash
make up
```

Then open **http://localhost:8080**.

```bash
make down     # stop it
make logs     # follow the log
make shell    # open a shell inside the container
make clean    # stop it and throw away its dependencies, its uploads and its image
make help     # list every target
```

Another port: `make up PORT=9000`.

Without the Makefile it is plain compose, from this directory:

```bash
docker compose up
docker compose down
```

The first start installs the dependencies inside the container, so a fresh clone needs nothing else. The example
installs chunky out of the checkout it sits in — see the `repositories` section of `composer.json` — so it always runs
against the code next to it.

## What to try

- **Pick a file and watch it go up.** It is cut into chunks of 256 KB and every chunk is one request. The chunk size
  can be changed on the page.
- **Pause it in the middle, then resume.** The upload goes on where it stopped.
- **Pause it, reload the page and pick the same file again.** The browser asks the server how much of that file is
  already there and continues from that byte. Nothing that was sent is sent twice.
- **Download a file with a download manager** and pause it halfway. `StreamOut` answers the `Range` header, so the
  manager can take the file in several pieces at once and resume a broken download.
- **Open [the formats page](http://localhost:8080/formats.php)** to see the same file sent as a plain upload, as a
  base64 data uri and as a json payload, all in one request.

## How it works

```
public/index.php     the page
public/assets/app.js cuts the file into chunks and sends them, one request each
public/upload.php    StreamIn — writes every chunk at the offset of its range
public/download.php  StreamOut — answers the Range header of a download
public/files.php     what is in the upload directory, and how much of it (used to resume)
public/delete.php    throws an uploaded file away
public/formats.php   the three formats a file can be sent in
src/                 RangeAwareCollector, the only glue the example needs
uploads/             where the files end up
```

The whole protocol is one extra field. Every request carries the chunk itself and a `range` saying which bytes of the
whole file it holds:

```
POST /upload.php
media: <the bytes of this chunk, with the name of the whole file>
range: 262144-524287
```

The server puts the two together and lets the package do the rest:

```php
$stream = new StreamIn('media', __DIR__.'/../uploads', new RangeAwareCollector('media'));

$stored = $stream->process();
```

`RangeAwareCollector` exists because `$_FILES` has nowhere to keep a range — it holds a name, a type and a size, and
that is all. A base64 data uri and a json payload carry theirs in their own meta section, so they need no field next
to them; see `formats.php`.

Chunks may arrive in any order, and one that arrives twice simply overwrites itself, so a client that resends the
chunk it is unsure about costs nothing but the bytes.

## This is a demo

It is written to be read, not to be deployed. It has no accounts, no upload limits, no check on what a file contains
and no cleaning up of half-finished uploads. Anyone who can reach it can write into the upload directory and read
back out of it. Take the idea, not the file.

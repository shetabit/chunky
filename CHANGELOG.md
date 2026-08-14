# Changelog

All Notable changes to `shetabit/chunky` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## Unreleased

### Added
- **`Classes\ChunkedUploadCollector`.** It reads the attributes of a chunk out of the request it arrived in, which is
  how a plain upload carries what `$_FILES` has no room for: its range and the size of the file it belongs to. A field
  holding a single value belongs to every chunk of the request, a field holding a list (`name="range[]"`) belongs to
  the chunks one by one, so several files can go up in one request. Which attributes are read, and out of which
  fields, is up to the caller; `range` and `size` are the default.
- **Validations.** An incoming stream can be told what it takes, and every chunk is checked before anything of it is
  written: `Validations\Size` (by the size the client announced for the whole file, and by the bytes of the chunk that
  arrived, written as `5M`, `512 KB` or a number), `Validations\Extension` (what a file is called) and
  `Validations\MimeType` (what it says it is, and — for the chunk that holds the beginning of a file — what its bytes
  say it is). `Contracts\ValidationInterface` is there to write your own. A request that carries a single chunk too
  many is turned away whole, so it leaves no half a file behind, and `ValidationFailedException::getErrors()` says
  everything that is wrong at once.
- **Events.** `beforeValidate()`, `afterValidate()`, `beforeUpload()` and `afterUpload()` on the incoming stream.
  `afterUpload()` is handed how much of the file is on disk, which is how a run recognises the last chunk of a file.
- `StreamIn::validate()`, which looks a request over and writes nothing, and a fourth constructor argument holding the
  validations.
- The entries `StreamIn::process()` returns carry an `assembled` key: the size of the file after that chunk was
  written.
- A test suite of 250 tests covering every class of the package: the file, the temporary file, the two adapters, the
  collector and both streams. Next to the unit tests there are feature tests that send a file chunk by chunk — in
  every order the chunks can arrive in, and in all three formats the package reads — and download it again range by
  range, with an interruption in the middle.
- GitHub Actions workflows running the test suite (PHP 8.4 and 8.5, lowest and highest dependencies), the coding style
  check, the static analysis and the code coverage on every pull request and on every push to `master`. The coverage
  has to stay above 90%.
- A `Dockerfile` (with pcov, for coverage) and a `Makefile` to run the test suite and every check inside a container.
- A `phpcs.xml.dist` ruleset, a `phpstan.neon.dist` (level 7, no baseline) and a `rector.php`.
- The `analyse`, `rector`, `test-coverage` and `ci` composer scripts.
- `File::exists()`, `File::getSize()`, `File::getAttributes()` and a public `File::close()`.
- `StreamIn::getFiles()` and `StreamIn::getPath()`, and `StreamOut::getPath()`, `getName()`, `getSize()`, `getMime()`
  and `getBoundary()`.
- `Shetabit\Chunky\Exceptions\ChunkyException` and the three exceptions that extend it: `FileNotFoundException`,
  `FileAccessException` and `InvalidDataException`.

### Changed
- **Breaking:** PHP 8.4 is now the minimum required version (was PHP 7.2).
- The package was modernized for PHP 8.4. Every parameter, return value and property of `src/` declares a type now,
  typed class constants and promoted constructor properties are used, and `new Foo()->bar()` replaces
  `(new Foo())->bar()`. Nullable types are spelled `T|null`.
- **Breaking:** the methods of `FileInterface`, `TempFileInterface`, `TempFileAdapterInterface` and of every class
  implementing them declare their types. An implementation of one of those interfaces has to declare compatible types.
  `read()` returns `string` instead of `string|false`, and `create()`, `write()`, `remove()`, `saveAs()` and
  `setEncodedData()` return `static`.
- **Breaking:** a file that can not be read, written or decoded throws instead of failing quietly. `StreamOut` throws
  a `FileNotFoundException` for a file that is not there, where it used to send a `400` and end the whole script with
  `die()` — which left the application no way of answering the request itself.
- **Breaking:** `StreamOut::process()` no longer ends the script on a range that can not be served. It answers with a
  `416 Requested Range Not Satisfiable` and returns.
- `StreamOut::process()` takes the `Range` header as an argument. Without one it reads the header of the request, the
  way it always did, so nothing has to change for an application that used it.
- `StreamOut` serves several ranges as `multipart/byteranges`, the type [RFC 7233](https://www.rfc-editor.org/rfc/rfc7233)
  names, instead of the `multipart/x-byteranges` of an early draft.
- `StreamOut` reads the file in buffers of 8 KB (the size is an argument of the constructor) instead of line by line
  with `fgets()`, which is a strange way to read a video file.
- `ext-fileinfo`, `ext-json` and `ext-mbstring` are required explicitly. `mime_content_type()` and `json_decode()` were
  always used, they were simply never declared.
- The development dependencies were updated: PHPUnit 11.5 to 13, PHP_CodeSniffer 4 (through
  `phpcsstandards/php_codesniffer`), PHPStan 2.2 and Rector 2.6.
- `phpunit.xml` was migrated to the current PHPUnit schema and made strict about risky tests, warnings, notices and
  deprecations. The suite is split into a `Unit` and a `Feature` testsuite.

### Removed
- **Breaking:** `Shetabit\Chunky\Classes\Uploader`, which could not run: it referenced `JsonToTempFile` and
  `Base64ToTempFile` without importing them, so PHP looked for them in `Shetabit\Chunky\Classes` and found nothing.
  `Collector` does the same job and is what the readme has always pointed at.
- **Breaking:** `Shetabit\Chunky\Contracts\ChunkInterface`, which nothing implemented and nothing used.
- The Travis CI configuration (`.travis.yml`), replaced by GitHub Actions.
- The StyleCI configuration (`.styleci.yml`), replaced by the PHP_CodeSniffer workflow.
- The `branch-alias` of `composer.json`.

### Fixed
- **Chunks are written where they belong.** `File::write()` opened the file in append mode (`a`), and in append mode
  every write goes to the end of the file no matter where the handle was moved to. The offset the file was seeked to
  was thrown away, so chunks were appended in the order they arrived in — a file whose chunks did not arrive in order
  came out shuffled. The file is opened in `c+` mode now, which keeps what is already in it and writes where it is
  told to.
- **A chunk is read from its own beginning.** `TempFile::saveAs()` read the chunk starting at the offset of the
  destination, so a chunk that belongs at byte 1024 of the file was read starting at byte 1024 *of itself* — past its
  own end, which is nothing at all. Only the first chunk of a file ever arrived.
- **A range is turned into an offset and a length.** `StreamIn::getRange()` handed the two ends of the range
  (`1024-2047`) to `saveAs()` as its offset and its length. The second one is a position, not a length.
- **A chunk can not be stored outside of the upload directory.** `StreamIn` built the path of a chunk out of the name
  the client sent, so a name like `../../config.php` wrote wherever it pointed. Only the file name of it is used now.
- **A single range is no longer followed by the rest of the file.** `StreamOut` wrote the range that was asked for and
  then went on to write everything behind it, which is more than the `Content-Length` of the response announces.
- **A json payload with a meta section works.** `JsonToTempFile` decoded the payload into objects and then read
  `$meta['size']` off of the `meta` object, which is a fatal error. The payload is decoded into arrays now.
- `hasJsonFormat()` uses `json_validate()`, which PHP 8.3 added and which says whether a string is json without
  building the whole thing first.
- **A payload that holds no file is left alone.** Anything that decoded as json — a number, a string, `null`, an
  object without a `data` key — used to be collected as a file and to fall apart the moment something was read out of
  it. The same for a data uri: `Base64ToTempFile` read its matches before checking that the pattern matched at all.
- **The meta section can not overwrite the payload.** A data uri or a json payload carrying a `data` of its own in its
  meta section replaced the file that was sent with it.
- **Files are not left open.** Every read and every write opened a new handle and put it on a pile that was only
  emptied when the object was collected, and `fclose()` was called on whatever was on that pile — including the
  `false` of an `fopen()` that failed, which is a `TypeError` on PHP 8. A file keeps a single handle now and closes it.
- **The size of a file is up to date.** `File::read()` asked `filesize()` for the size of the file, which is answered
  from a cache that a write of that very file does not clear. The size is read from the open handle now.
- `File::read()` no longer fails on an empty file (`fread()` refuses a length of `0` on PHP 8), on an offset behind the
  end of the file or on a file that does not exist — the last one throws a `FileNotFoundException` instead of warning
  and returning nothing.
- `StreamOut` reads `Range` headers the way [RFC 7233](https://www.rfc-editor.org/rfc/rfc7233) describes them: an open
  range (`bytes=500-`), a suffix range (`bytes=-500`), a range that reaches past the end of the file and a list of
  ranges of which only some can be served are all answered correctly, and a unit other than `bytes` is answered with
  the whole file.
- The description and the keywords of `composer.json` no longer say "resummable".

## Date - 2019-01-09

### Fixed
- Nothing

### Added
- Nothing

### Deprecated
- Nothing

### Fixed
- Nothing

### Removed
- Nothing

### Security
- Nothing

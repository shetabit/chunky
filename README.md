<p align="center"><img src="resources/images/chunky.png?raw=true"></p>

# Chunky :)

handle and upload files in `base64 data URI`, `base64 json format`  and `normal file` formats.

[![Software License][ico-license]](LICENSE.md)
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads on Packagist][ico-download]][link-packagist]
[![Tests][ico-tests]][link-tests]
[![Code Style][ico-code-style]][link-code-style]
[![Static Analysis][ico-static-analysis]][link-static-analysis]
[![Code Coverage][ico-coverage]][link-coverage]

This package supports `PHP 8.4+` and has no dependencies of its own, so it can be used in any PHP framework —
`Laravel`, `Symfony` and the rest — as well as in no framework at all.

> Chunky can be used to handle input/output file streams, both of `input` and `output` streams can be **multi-chunk** and **resumable**.

# List of contents

- [Install](#install)
- [A running example](#a-running-example)
- [How to use](#how-to-use)
  - [Stream output](#stream-output)
  - [Stream input](#stream-input)
  - [The formats a chunk can be sent in](#the-formats-a-chunk-can-be-sent-in)
  - [Collect and store input files](#collect-and-store-input-files)
  - [Create stream in Laravel Framework](#create-stream-in-laravel-framework)
    - [Download stream](#resumable-chunk-download-stream-example-in-laravel)
    - [Upload stream](#resumable-chunk-upload-stream-example-in-laravel)
- [Errors](#errors)
- [Testing](#testing)
- [Change log](#change-log)
- [Contributing](#contributing)
- [Security](#security)
- [Credits](#credits)
- [License](#license)

## Install

Via Composer

``` bash
$ composer require shetabit/chunky
```

## A running example

There is a small application in [examples/](examples) that uploads a file chunk by chunk — with a pause and a resume
— and hands it back out as a resumable download. It is an application of its own, with its own `composer.json` and
its own container, and it is not installed with the package.

```bash
cd examples
make up      # then open http://localhost:8080
make down
```

See [examples/README.md](examples/README.md) for what to try and how the pieces fit together.

## how-to-use

this package can be used in order to stream input/output files.

- stream output files (stream download)
- stream input files (stream upload)

#### Stream output

outputs can be streamed using `Shetabit\Chunky\Classes\StreamOut` like the below

```php
# On the top of the file.
use Shetabit\Chunky\Classes\StreamOut;
...

$path = '../path/to/yourfile.mp3';
$stream = new StreamOut($path);
$stream->process();
```

the **download stream** will be **multi-chunk** and **resumable** as you see in the below screenshot (in Internet download manager)

<p align="center"><img src="resources/images/output-stream-screenshot.png?raw=true"></p>

The stream reads the `Range` header of the request on its own. If your framework hands you the headers of a request
itself, you can pass the header along instead:

```php
$stream->process($request->header('Range'));
```

A client that asks for a part of the file receives a `206 Partial Content`, one that asks for a range that is not
there receives a `416 Requested Range Not Satisfiable`, and one that asks for several ranges at once receives them as
a `multipart/byteranges` response.

The stream can be throttled, and the size of the buffer it writes can be changed:

```php
$delay = 1000; // wait a millisecond after every buffer
$bufferSize = 16 * 1024; // write 16 KB at a time

$stream = new StreamOut($path, $delay, $bufferSize);
```

#### Stream input

input files can be uploaded chunk by chunk and resumable. you can collect files and upload them like the below

```php
# On the top of the file.
use Shetabit\Chunky\Classes\StreamIn;
...

$inputName = 'media'; // html input's name
$uploadPath = './path/to/address';

$inputStream = new StreamIn($inputName, $uploadPath);
$uploads = $inputStream->process();
```

after uploading files, they will be in `$uploads` in array format: one entry per chunk, each of them holding the
`path` the chunk was written to, the `range` of the destination it was written at (`[offset, length]`) and the `file`
itself.

A chunk carries the bytes of the whole file it holds in its `range` (`1024-2047`), and that is where it is written —
whatever order the chunks arrive in. The name a chunk is stored under is its own name with everything but the file
name cut off it, so a client can not write outside of the upload directory.

#### The formats a chunk can be sent in

The same input can be sent in three formats, and all three of them are collected together:

| format | where it is read from | example |
| --- | --- | --- |
| an uploaded file | `$_FILES[$inputName]` | `<input name="media" type="file">` |
| a base64 data uri | `$_REQUEST[$inputName]` | `data:image/png;name=avatar.png;range=0-1023;base64,iVBORw0KGgo...` |
| a json payload | `$_REQUEST[$inputName]` | `{"mime":"image/png","meta":{"name":"avatar.png","range":"0-1023"},"data":"iVBORw0KGgo..."}` |

The `name`, the `range` and the `size` of the meta section of a data uri and of the `meta` of a json payload become
the attributes of the file the package hands you.

#### Collect and store input files

you can collect input files and then upload them as you want.

```php
# On the top of the file.
use Shetabit\Chunky\Classes\Collector;
...

// html: <input name="media" type="file">
$inputName = 'media';
$collector = new Collector($inputName);

// collect all input files
$files = $collector->collect();

$file = $files[0]; // retrieve the first file

// you can store each file like the below
$path = './path/to/filename.jpg';
$file->saveAs($path); // save file as filename.jpg

// or we can use file's original name
$path = './path/to/'.$file->name;
$file->saveAs($path);
```

`saveAs()` takes the offset of the destination the file is written at, and how many of its bytes to write:

```php
// write the first 1024 bytes of the chunk at byte 4096 of the destination
$file->saveAs($path, 4096, 1024);
```

#### Create stream in Laravel Framework

###### Resumable chunk download stream example in Laravel

create a controller like the below and create an indirect resumable file stream in Laravel.

```php
namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Shetabit\Chunky\Classes\StreamOut;

class StreamOutController extends Controller
{
    /**
     * Stream file output
     */
    public function __invoke(Request $request, File $file): void
    {
        // proceed until all of the file has been sent to the client
        ini_set('max_execution_time', '0');

        // retrieve file's path
        $path = '../'.$file->path;

        // prepare stream
        $stream = new StreamOut($path);

        // run stream
        $stream->process($request->header('Range'));
    }
}
```

in this example we have a `File` eloquent model.

###### Resumable chunk upload stream example in Laravel

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Shetabit\Chunky\Classes\Collector;
use Shetabit\Chunky\Classes\StreamIn;

class StreamInController extends Controller
{
    /**
     * Stream file input
     */
    public function __invoke(Request $request): void
    {
        /**
            if you want simple file upload (not resumable and chunk)
            you can use the below code
        **/

        $inputName = 'media';
        $collector = new Collector($inputName);

        // collect all input files
        $files = $collector->collect();

        $file = $files[0]; // retrieve the first file

        // you can store each file like the below
        $path = './path/to/filename.jpg';
        $file->saveAs($path); // save file as filename.jpg

        // or we can use file's original name
        $path = './path/to/'.$file->name;
        $file->saveAs($path);

        // ---------------------------------------------

        /**
            if you want advanced file upload (resumable and chunk)
            you can use the below code
        **/

        $inputName = 'media'; // html input's name
        $uploadPath = './path/to/address';

        $inputStream = new StreamIn($inputName, $uploadPath);
        $uploads = $inputStream->process();
    }
}
```

## Errors

Everything the package throws extends `Shetabit\Chunky\Exceptions\ChunkyException`, so a single `catch` covers all of
it:

| exception | when it is thrown |
| --- | --- |
| `FileNotFoundException` | a file that has to be read is not there, or the upload directory does not exist |
| `FileAccessException` | a file exists but could not be opened or written to |
| `InvalidDataException` | a data uri or a json payload could not be decoded |

## Testing

Every pull request and every push to `master` is checked by [GitHub Actions][link-actions]: the test suite runs on
PHP 8.4 and 8.5 (against both the lowest and the highest supported dependencies), the coding style is checked with
PHP_CodeSniffer, the sources are analysed with PHPStan and the code coverage of the test suite is measured and has to
stay above 90%.

Next to the unit tests there are feature tests that send a whole file chunk by chunk — in every order the chunks can
arrive in, and in all three formats the package reads — and download it again range by range, with an interruption in
the middle.

You can run the same checks locally. With PHP and Composer installed on your machine:

```bash
composer install

composer test           # run the test suite
composer test-coverage  # run the test suite and report code coverage
composer check-style    # check the coding style
composer fix-style      # fix the coding style where possible
composer analyse        # run static analysis
composer ci             # run all of the checks above
```

If you would rather not install PHP on your machine, the shipped `Dockerfile` and `Makefile` run everything inside a
container:

```bash
make test              # run the test suite
make coverage          # run the test suite and report code coverage
make check-style       # check the coding style
make fix-style         # fix the coding style where possible
make analyse           # run static analysis
make ci                # run all of the checks above
make shell             # open a shell inside the container
make help              # list every available target
```

Another PHP version can be used with `make test PHP_VERSION=8.5`.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email khanzadimahdi@gmail.com instead of using the issue tracker.

## Credits

- [Mahdi khanzadi][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/shetabit/chunky.svg?style=flat-square
[ico-download]: https://img.shields.io/packagist/dt/shetabit/chunky.svg?color=%23F18&style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-tests]: https://img.shields.io/github/actions/workflow/status/shetabit/chunky/tests.yml?branch=master&label=Tests&style=flat-square
[ico-code-style]: https://img.shields.io/github/actions/workflow/status/shetabit/chunky/code-style.yml?branch=master&label=Code%20Style&style=flat-square
[ico-static-analysis]: https://img.shields.io/github/actions/workflow/status/shetabit/chunky/static-analysis.yml?branch=master&label=Static%20Analysis&style=flat-square
[ico-coverage]: https://img.shields.io/codecov/c/github/shetabit/chunky/master?label=Coverage&style=flat-square

[link-packagist]: https://packagist.org/packages/shetabit/chunky
[link-actions]: https://github.com/shetabit/chunky/actions
[link-tests]: https://github.com/shetabit/chunky/actions/workflows/tests.yml
[link-code-style]: https://github.com/shetabit/chunky/actions/workflows/code-style.yml
[link-static-analysis]: https://github.com/shetabit/chunky/actions/workflows/static-analysis.yml
[link-coverage]: https://codecov.io/gh/shetabit/chunky
[link-author]: https://github.com/khanzadimahdi
[link-contributors]: ../../contributors

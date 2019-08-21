<p align="center"><img src="resources/images/chunky.png?raw=true"></p>

# Chunky :)

handle and upload files in `base64 data URI`, `base64 json format`  and `normal file` formats.

> this package can be used in all php frameworks for example `Laravel`, `Symfony` and etc.

[![Software License][ico-license]](LICENSE.md)

> Chunky can be used to handle input/output file streams, both of `input` and `output` streams can be **multi-chunk** and **resumable**.

# List of contents

- [Install](#install)
- [How to use](#how-to-use)
  - [Stream output](#stream-output)
  - [Stream input](#stream-input)
  - [Collect and store input files](#collect-and-store-input-files)
  - [Create stream in Laravel Framework](#create-stream-in-laravel-framework)
    - [Download stream](#resumable-chunk-download-stream-example-in-laravel)
    - [Upload stream](#resumable-chunk-upload-stream-example-in-laravel)
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

## how-to-use

this package can be used in order to stream input/output files.

- stream output files (stream download)
- stream input files (stream upload)

#### Stream output
ouputs can be streamed using `Shetabit\Chunky\Classes\StreamOut` like the below

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

#### Stream input

input files can be uploaded chunk by chunk and resumable. you can collect files and upload them like the below

```php
# On the top of the file.
use Shetabit\Chunky\Classes\StreamIn;
...

$inputName = 'file'; // html input's name
$uploadPath = './path/to/address';

$inputStream = new StreamIn('file', $uploadPath);
$uploads = $inputStream->process();
```
after uploading files, they will be in `$uploads` in array format.

#### Collect and store input files

you can collect input files and then upload them as you want. 

```php
# On the top of the file.
use Shetabit\Chunky\Classes\Collector;
...

// html: <input name="media" type="file">
$inputName = 'media';
$this->collector = new Collector($inputName);

// collect all input files
$files = $this->files = $this->collector->collect();

$file = $files[0]; // retrieve the first file

// you can store each file like the below
$path = './path/to/filename.jpg';
$file->saveAs($path); // save file as filename.jpg

// or we can use file's original name
$path = './path/to/'.$file->name;
$file->saveAs($path);
```

#### Create stream in Laravel Framework

###### Resumable chunk download stream example in Laravel

create a controller like the below and create an indirect resumable file stream in Laravel.

```php
namespace App\Http\Controllers;

use App\Models\File;
use Shetabit\Chunky\Classes\StreamOut;

class StreamOutController extends Controller
{
    /**
     * Stream file output
     *
     * @param File $file
     */
    public function __invoke(File $file)
    {
	    // procceed until all file has sent to client
        ini_set('max_execution_time', '0');
    
	    // retrieve file's path
        $path = '../'.$file->path; 
		
		// prepare stream
        $stream = new StreamOut($path);

		// run stream
        $stream->process();
    }
}
```

in this example we have a `File` eloquent model.

###### Resumable chunk upload stream example in Laravel

```php
namespace App\Http\Controllers;

use Shetabit\Chunky\Classes\Collector;
use Shetabit\Chunky\Classes\StreamIn;

class StreamInController extends Controller
{
    /**
     * Stream file input
     *
     * @param Request $request
     */
    public function __invoke(Request $request)
    {    
    	/**
    		if you want simple file upload (not resumable and chunk)
    		you can use the below code
    	**/
    
        $inputName = 'media';
        $this->collector = new Collector($inputName);

        // collect all input files
        $files = $this->files = $this->collector->collect();

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

        $inputStream = new StreamIn('file', $uploadPath);
        $uploads = $inputStream->process();
    }
}
```

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

[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square

[link-author]: https://github.com/khanzadimahdi
[link-contributors]: ../../contributors

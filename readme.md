# Statix Server

An object oriented wrapper around PHP's built-in server.

## Requirements

- PHP 8 minumum

## Installation

```bash
composer require statix/server
```

## Basic Usage

To get started, ensure the vendor autoload script is required and then create an instance of the `Server` class, once you have set any [configuration options](#advanced-usage), you should call the `start` method to start the server. 

```php
use Statix\Server\Server;

require_once './vendor/autoload.php';

Server::new()->start();

// or 

(new Server)->start();
```

## Advanced Usage

You can configure the several options with the server, such as the host, the port, the root directory and more. Please read more below for a detailed explanation of each configuration method.

### Passing configuration via the constructor or Server::new()

You may pass most configuration options via the constructor as shown below where we set the `host`, `port` and `root` options. 

```PHP
Server::new([
    'host' => 'localhost',
    'port' => 8000,
    'root' => __DIR__ . '/content'
]);

// or 

new Server([
    'host' => 'localhost',
    'port' => 8000,
    'root' => __DIR__ . '/content'
]);

$optionsSettableViaContructor = [
    'host' => 'string', // default: localhost
    'port' => 'string|int', // default: 8000
    'root' => 'string', // default: getcwd()
    'router' => 'string', // path to your routing script
    'executable' => 'string', // path to the desired PHP binary to use for the server
    'withoutEnvVars' => [ 
        'secret' => 'key'
    ]
];
```

### Setting configuration via the named methods

```PHP
use Statix\Server;

Server::new()
    ->usePHP('path') 
    ->onHost('localhost') 
    ->onPort('8080') 
    ->root('./content')
    ->useRouter('./router.php')
    ->withoutEnvVars([
        //
    ])->filterEnvVars(function($value, $key) { 
        //
    })
```

### Capturing the output from the server process

If you want to show the output from the server process as it recieves and handles requests, you may call the `output` method and pass a callback function that will be called and passed any output of the process. One thing to note is that this method will only be called when the process is not running in the background.

```PHP
Server::new()
    ->output(function($output) {
        echo $output;
    })->start();
```

### Running the process in the background

You may find it useful to run the server process in the background, you may call `runInBackground()`. The process will run as long as the parent script is running. 

```PHP
Server::new()->runInBackground();
```

## Contributing

#### Installation

1. Clone repo 

```
git clone https://github.com/statix-php/server.git
```

2. Install php dependencies

```
composer install
```

#### Testing

We use [Pest PHP](https://pestphp.com/) for the test suite, please ensure before pushing changes you confirm there are no breaking changes by running the command below. Additionally, tests for new features are highly encouraged, changes will be considered without tests but it will increase the time to accept / merge. 

```bin
./vendor/bin/pest
```

#### Style

We use [Laravel Pint](https://github.com/laravel/pint) to automatically standardize code styling, before pushing changes please run `pint` using the command below. 

```bin
./vendor/bin/pint
```
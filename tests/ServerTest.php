<?php

use Statix\Server\Server;
use Symfony\Component\Process\Process;

test('it can be created using static method', function () {
    expect(Server::new())->toBeInstanceOf(Server::class);
});

test('it can be created using normal constructor', function () {
    expect(new Server)->toBeInstanceOf(Server::class);
});

// host
test('the default host is localhost', function () {
    expect(Server::new()->getConfiguration('host'))
        ->toEqual('localhost');
});

test('the host can be changed via the constructor', function () {
    expect(Server::new([
        'host' => 'example.test',
    ])->getConfiguration('host'))
        ->toEqual('example.test');
});

test('the host can be changed via named method', function () {
    expect(Server::new()
        ->host('example.test')
        ->getConfiguration('host'))
        ->toEqual('example.test');
});

// port
test('the default port is 8000', function () {
    expect(Server::new()->getConfiguration('port'))
        ->toEqual('8000');
});

test('the port can be changed via the constructor', function () {
    expect(Server::new([
        'port' => '8080',
    ])->getConfiguration('port'))
    ->toEqual('8080');
});

test('the port can be changed via named method', function () {
    expect(Server::new()
        ->port('8080')
        ->getConfiguration('port'))
        ->toEqual('8080');
});

// root
test('the default root is the current working directory', function () {
    expect(Server::new()->getConfiguration('root'))
        ->toEqual(getcwd());
});

test('the root can be changed via the constructor', function () {
    expect(Server::new([
        'root' => './content',
    ])->getConfiguration('root'))
        ->toEqual('./content');
});

test('the root can be changed via named method', function () {
    expect(Server::new()
        ->root('./tests/resources')
        ->getConfiguration('root'))
        ->toEqual('./tests/resources');
});

// router
test('the default router is null', function () {
    expect(Server::new()->getConfiguration('router'))
        ->toEqual(null);
});

test('the router can be changed via the constructor', function () {
    expect(Server::new([
        'router' => './tests/resources/router.php',
    ])->getConfiguration('router'))
        ->toEqual('./tests/resources/router.php');
});

test('the router can be changed via named method', function () {
    expect(Server::new()
        ->router('./tests/resources/router.php')
        ->getConfiguration('router'))
        ->toEqual('./tests/resources/router.php');
});

test('an exception is thrown if the router path does not exist', function () {
    expect(function () {
        return Server::new()
            ->router('./tests/resources/faker-router.php');
    })->toThrow(\Exception::class);
});

// withEnvVars
test('the default withEnvVars array is null', function () {
    expect(Server::new()->getConfiguration('withEnvVars'))
        ->toEqual([]);
});

// withoutEnvVars
test('the default withoutEnvVars array is null', function () {
    expect(Server::new()->getConfiguration('withoutEnvVars'))
        ->toEqual([]);
});

// runInBackground
test('the runInBackground method works', function () {
    $url = 'http://localhost:8000';

    expect(function () use ($url) {
        get($url);
    })->toThrow(\Illuminate\Http\Client\ConnectionException::class);

    $server = Server::new([
        'root' => './tests/resources',
    ])->runInBackground();

    expect(get($url)->successful())->toBe(true);

    $server->stop();
});

// getProcess
test('the getProcess method returns null if server has not started', function () {
    $server = Server::new();

    expect($server->getProcess())->toBe(null);
});

test('the getProcess method returns a process instance', function () {
    $server = Server::new()->runInBackground();

    expect($server->getProcess())->toBeInstanceOf(Process::class);
});

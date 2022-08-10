<?php

namespace Statix\Server;

use Dotenv\Dotenv;
use Exception;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class Server
{
    /**
     * The user passed configuration array
     *
     * @var  array
     */
    protected $baseConfiguration;

    /**
     * The default and user merged configuration array
     *
     * @var  array
     */
    protected $configuration;

    /**
     * The output handler to pass process output to
     *
     * @var  callable
     */
    protected $outputHandler;

    /**
     * The env vars which will be passed to the server process
     *
     * @var  array
     */
    protected $envVarsToPass;

    /**
     * Indicator whether or not server is currently running
     *
     * @var  bool
     */
    protected $running = false;

    /**
     * The running process
     *
     * @var  Process
     */
    protected $process = null;

    public static function new(array $configuration = []): self
    {
        return new self($configuration);
    }

    public function __construct(array $configuration = [])
    {
        $this->baseConfiguration = $configuration;

        $this->configuration = array_merge($defaults = [
            'host' => 'localhost',
            'port' => 8000,
            'root' => getcwd(),
            'php' => null,
            'router' => null,
            'withEnvVars' => [],
            'withoutEnvVars' => [],
        ], $configuration);

        $this->envVarsToPass = array_merge($_ENV, getenv(), $_SERVER);

        return $this;
    }

    /**
     * Filter out environment variables from getting passed to the
     * server process. If the callback returns false the variable
     * will be removed. The callback will recieve the value and
     * key, in that order.
     *
     * @param  callable  $callback
     * @return  self
     */
    public function filterEnvVars(callable $callback): self
    {
        $this->envVarsToPass = array_filter(
            $this->envVarsToPass,
            $callback,
            ARRAY_FILTER_USE_BOTH
        );

        return $this;
    }

    private function findExecutable(): string
    {
        if ($this->configuration['php'] != null) {
            return $this->configuration['php'];
        }

        return (new PhpExecutableFinder)->find(false);
    }

    /**
     * Get the full addess for the server.
     * Example: http://localhost:8000
     *
     * @return  string
     */
    public function getAddress(): string
    {
        return 'http://'.$this->configuration['host'].':'.$this->configuration['port'];
    }

    /**
     * Retrieve either the entire configuration
     * array or a specific key.
     *
     * @param  string  $key
     * @return  mixed
     */
    public function getConfiguration(string $key = null): mixed
    {
        if ($key != null) {
            return $this->configuration[$key] ?? null;
        }

        return $this->configuration;
    }

    /**
     * Retrieve the process once the server is running
     * in the background, or null before
     *
     * @param  string  $key
     * @return  Process|null
     */
    public function getProcess(): Process|null
    {
        return ($this->process) ? $this->process : null;
    }

    /**
     * Set the host for the server.
     *
     * @param  string  $host
     * @return  self
     */
    public function host(string $host): self
    {
        $this->configuration['host'] = (string) ltrim(ltrim($host, 'http://'), 'https://');

        return $this;
    }

    /**
     * Determine if the server is running.
     *
     * @return  bool
     */
    public function isRunning(): bool
    {
        return (bool) $this->running;
    }

    /**
     * Capture the output from the server process
     * and pass to the given callback
     *
     * @param  callable  $callback
     * @return  self
     */
    public function output(callable $callback): self
    {
        $this->outputHandler = $callback;

        return $this;
    }

    /**
     * Set the path for the PHP executable.
     *
     * @param  string  $path
     * @return  self
     *
     * @throws \Exception
     */
    public function php(string $path): self
    {
        if (! is_executable($path)) {
            throw new Exception('PHP executable path is not executable: '.$path);
        }

        $this->configuration['php'] = (string) $path;

        return $this;
    }

    /**
     * Set the port for the server.
     *
     * @param  string  $port
     * @return  self
     */
    public function port(mixed $port): self
    {
        $this->configuration['port'] = (string) $port;

        return $this;
    }

    /**
     * Set the root directory for the server.
     *
     * @param  string  $root
     * @return  self
     */
    public function root(string $root): self
    {
        if (! is_dir($root)) {
            throw new Exception('Root path is not a directory: '.$root);
        }

        $this->configuration['root'] = (string) $root;

        return $this;
    }

    /**
     * Set the path to the routing script for the server.
     *
     * @param  string  $path
     * @return  self
     *
     * @throws \Exception
     */
    public function router(string $path): self
    {
        if (! file_exists($path)) {
            throw new Exception('The router file does not exist: '.$path);
        }

        $this->configuration['router'] = (string) $path;

        return $this;
    }

    /**
     * Load the given .env file and ensure env vars
     * are passed to server process.
     *
     * @param  string  $path
     * @return  self
     *
     * @throws \Exception
     */
    public function withEnvFile(string $path): self
    {
        if (! file_exists($path)) {
            throw new Exception('Given path to env file does not exists: '.$path);
        }

        (Dotenv::createImmutable(
            dirname($path),
            basename($path)
        ))->safeLoad();

        $this->envVarsToPass = array_merge($_ENV, getenv(), $_SERVER);

        return $this;
    }

    /**
     * Ensure the given array is passed into env vars for
     * server process.
     *
     * @param  array  $vars
     * @return  self
     */
    public function withEnvVars(array $vars): self
    {
        $this->envVarsToPass = array_merge(
            $this->envVarsToPass,
            $vars
        );

        return $this;
    }

    /**
     * Ensure the given array of keys is not present in
     * the env vars passed to the server process.
     *
     * @param  array  $vars
     * @return  self
     */
    public function withoutEnvVars(array $vars): self
    {
        $this->envVarsToPass = array_filter(
            $this->envVarsToPass,
            function ($key) use ($vars) {
                return ! in_array($key, $vars);
            },
            ARRAY_FILTER_USE_KEY
        );

        return $this;
    }

    private function buildServeCommand(): array
    {
        $command = [
            $this->findExecutable(),
            '-S',
            $this->configuration['host'].':'.$this->configuration['port'],
            '-t',
            $this->configuration['root'],
        ];

        if ($this->configuration['router'] != null) {
            array_push($command, $this->configuration['router']);
        }

        return $command;
    }

    private function buildPassingEnvVarArray(): array
    {
        return array_merge(array_filter($this->envVarsToPass, function ($key) {
            return ! in_array($key, $this->configuration['withoutEnvVars']);
        }, ARRAY_FILTER_USE_KEY), $this->configuration['withEnvVars']);
    }

    private function initProcess(): Process
    {
        $process = new Process(
            $this->buildServeCommand(),
            null,
            $this->buildPassingEnvVarArray(),
            null,
            null
        );

        $process->start(function ($type, $buffer) {
            if ($this->outputHandler != null) {
                ($this->outputHandler)($buffer);
            }
        });

        $this->running = true;

        return $process;
    }

    /**
     * Start the server and hold script execution
     * until the script is ended. Will return
     * the process exit code.
     *
     * @return  int
     */
    public function start(): int
    {
        $this->process = $this->initProcess();

        return $this->process->wait();
    }

    /**
     * Stop the server if running. Will return null if
     * the server was not running. Will return an array
     * with the exit code and exit code text if
     * server was running.
     *
     * @return  array|null
     */
    public function stop(): array|null
    {
        if ($this->isRunning()) {
            return null;
        }

        $this->process->stop(1);

        $this->running = false;

        return [
            $this->process->getExitCode(),
            $this->process->getExitCodeText(),
        ];
    }

    /**
     * Restart the server instance if its already running
     * or start the process.
     *
     * @return  self
     */
    public function restart(): self
    {
        if ($this->isRunning()) {
            $this->stop();
        }

        $this->process = $this->initProcess();

        return $this;
    }

    /**
     * Run the server process in the background.
     *
     * @return  self
     */
    public function runInBackground(): self
    {
        $this->process = $this->initProcess();

        return $this;
    }
}

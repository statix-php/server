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
     * @param  array  $baseConfiguration
     */
    protected $baseConfiguration;

    /**
     * The default and user merged configuration array
     *
     * @param  array  $configuration
     */
    protected $configuration;

    /**
     * The output handler to pass process output to
     *
     * @param  callable  $outputHandler
     */
    protected $outputHandler;

    /**
     * The env vars which will be passed to the server process
     *
     * @param  array  $envVarsToPass
     */
    protected $envVarsToPass;

    /**
     * Indicator whether or not server is currently running
     *
     * @param  bool  $running
     */
    protected $running = false;

    /**
     * The running process
     *
     * @param  Process  $process
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
            'executable' => null,
            'router' => null,
            'withEnvVars' => [],
            'withoutEnvVars' => [],
        ], $configuration);

        $this->envVarsToPass = array_merge($_ENV, getenv(), $_SERVER);

        return $this;
    }

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
        if ($this->configuration['executable'] != null) {
            return $this->configuration['executable'];
        }

        return (new PhpExecutableFinder)->find(false);
    }

    public function getConfiguration(string $key = null): mixed
    {
        if ($key != null) {
            return $this->configuration[$key] ?? null;
        }

        return $this->configuration;
    }

    public function getProcess(): Process|null
    {
        return ($this->process) ? $this->process : null;
    }

    public function host(string $host): self
    {
        $this->configuration['host'] = (string) ltrim(ltrim($host, 'http://'), 'https://');

        return $this;
    }

    public function isRunning(): bool
    {
        return (bool) $this->running;
    }

    public function output(callable $callback): self
    {
        $this->outputHandler = $callback;

        return $this;
    }

    public function php(string $executable): self
    {
        if (! is_executable($executable)) {
            throw new Exception('PHP executable path is not executable: '.$executable);
        }

        $this->configuration['executable'] = (string) $executable;

        return $this;
    }

    public function port(mixed $port): self
    {
        $this->configuration['port'] = (string) $port;

        return $this;
    }

    public function root(string $root): self
    {
        if (! is_dir($root)) {
            throw new Exception('Root path is not a directory: '.$root);
        }

        $this->configuration['root'] = (string) $root;

        return $this;
    }

    public function router(string $path): self
    {
        if (! file_exists($path)) {
            throw new Exception('The router file does not exist: '.$path);
        }

        $this->configuration['router'] = (string) $path;

        return $this;
    }

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

    public function withEnvVars(array $vars): self
    {
        $this->envVarsToPass = array_merge(
            $this->envVarsToPass,
            $vars
        );

        return $this;
    }

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

    public function start(): int
    {
        $this->process = $this->initProcess();

        return $this->process->wait();
    }

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

    public function restart(): self
    {
        if ($this->isRunning()) {
            $this->stop();
        }

        $this->process = $this->initProcess();

        return $this;
    }

    public function runInBackground(): self
    {
        $this->process = $this->initProcess();

        return $this;
    }
}

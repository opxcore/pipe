<?php
/*
 * This file is part of the OpxCore.
 *
 * Copyright (c) Lozovoy Vyacheslav <opxcore@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpxCore\Pipeline;

use OpxCore\Container\Interfaces\ContainerExceptionInterface;
use OpxCore\Container\Interfaces\ContainerInterface;
use OpxCore\Container\Interfaces\NotFoundExceptionInterface;
use OpxCore\Pipeline\Exceptions\PipelineException;

class Pipeline
{
    /**
     * @var ContainerInterface|null Container to resolve dependencies
     */
    protected ?ContainerInterface $container;

    /** @var mixed Passable to pass through pipe */
    protected $passable;

    /** @var array Pipes to pass passable through */
    protected array $pipes = [];

    /** @var string Method name to handle */
    protected string $method = 'handle';

    /** @var callable Callable to run at the end */
    protected $endpoint;

    /**
     * Pipe constructor.
     *
     * @param ContainerInterface|null $container
     *
     * @return  void
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipe.
     *
     * @param mixed $passable
     *
     * @return  $this
     */
    public function send($passable): Pipeline
    {
        $this->passable = $passable;

        return $this;
    }


    /**
     * Set the array of pipes.
     *
     * @param array $pipes
     *
     * @return  $this
     */
    public function through(array $pipes): Pipeline
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * Set the method to call on the pipes.
     *
     * @param string $method
     *
     * @return  $this
     */
    public function via(string $method): Pipeline
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Set endpoint callable at pipeline.
     *
     * @param callable $endpoint
     *
     * @return  $this
     */
    public function then(callable $endpoint): Pipeline
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Run pipeline processing.
     *
     * @return mixed
     *
     * @throws PipelineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run()
    {
        if (!isset($this->passable)) throw new PipelineException('Nothing to pass through');

        // Assign endpoint call if set
        if (isset($this->endpoint) && is_callable($this->endpoint)) {
            $run = function ($passable) {
                return call_user_func($this->endpoint, $passable);
            };
        } else {
            $run = function ($passable) {
                return $passable;
            };
        }

        // Skip pipes if they are empty
        if (count($this->pipes) === 0) {
            return $run
                ? call_user_func($run, $this->passable)
                : $this->passable;
        }

        // Build pipe stack
        $pipes = array_reverse($this->pipes, true);

        foreach ($pipes as $index => $pipe) {

            $arguments = [];

            if (!is_numeric($index)) {
                $arguments = $pipe;
                if (is_string($arguments)) {
                    $arguments = explode(',', $arguments);
                }
                $pipe = $index;
            }

            $pipeCallable = [$this->resolvePipe($pipe), $this->method];

            $run = function ($passable) use ($pipeCallable, $run, $arguments) {
                return call_user_func($pipeCallable, $passable, $run, ...$arguments);
            };
        }

        // Run and return result.
        return $run($this->passable);
    }

    /**
     * Resolve pipe instance.
     *
     * @param mixed $pipe
     *
     * @return  mixed
     *
     * @throws PipelineException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function resolvePipe($pipe)
    {
        if (!is_string($pipe)) return $pipe;

        if (!isset($this->container)) {
            throw new PipelineException('Container not set.');
        }

        return $this->container->make($pipe);
    }
}
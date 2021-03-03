<?php
/*
 * This file is part of the OpxCore.
 *
 * Copyright (c) Lozovoy Vyacheslav <opxcore@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpxCore\Tests\Pipeline;

use OpxCore\Container\Container;
use OpxCore\Pipeline\Exceptions\PipelineException;
use OpxCore\Pipeline\Pipeline;
use OpxCore\Tests\Pipeline\Fixtures\BrokenPipeOne;
use OpxCore\Tests\Pipeline\Fixtures\BypassPipe;
use OpxCore\Tests\Pipeline\Fixtures\PipeOne;
use OpxCore\Tests\Pipeline\Fixtures\PipeTwo;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{
    /**
     * @throws PipelineException
     */
    public function testSimplePipes(): void
    {
        $passable = '(0)';
        $pipeline = new Pipeline();

        $result = $pipeline
            ->send($passable)
            ->then(function ($passable) {
                return $passable .= '(e)';
            })
            ->run();

        self::assertEquals('(0)(e)', $result);
    }

    /**
     * @throws PipelineException
     */
    public function testVerySimplePipes(): void
    {
        $passable = '(0)';
        $container = new Container();
        $pipeline = new Pipeline($container);

        $result = $pipeline
            ->send($passable)
            ->run();

        self::assertEquals('(0)', $result);
    }

    /**
     * @throws PipelineException
     */
    public function testPipes(): void
    {
        $passable = '(0)';
        $container = new Container();
        $pipeline = new Pipeline($container);

        $result = $pipeline
            ->send($passable)
            ->through([
                PipeOne::class,
                PipeTwo::class
            ])
            ->via('handle')
            ->run();

        self::assertEquals('(0)(1)(2)(-2)(-1)', $result);
    }

    /**
     * @throws PipelineException
     */
    public function testArgumentPipes(): void
    {
        $passable = '(0)';
        $container = new Container();
        $pipeline = new Pipeline($container);

        $result = $pipeline
            ->send($passable)
            ->through([
                PipeOne::class => 'a',
                PipeTwo::class => 'b',
            ])
            ->via('handle')
            ->then(function ($passable) {
                return $passable .= '(e)';
            })
            ->run();

        self::assertEquals('(0)(1a)(2b)(e)(-2b)(-1a)', $result);
    }

    /**
     * Pipe without return will break previous pipeline processing and send null to $next().
     * This is not an error because returning null is an case.
     *
     * @throws PipelineException
     */
    public function testBrokenPipe(): void
    {
        $passable = '(0)';
        $container = new Container();
        $pipeline = new Pipeline($container);

        $result = $pipeline
            ->send($passable)
            ->through([
                PipeOne::class => 'a',
                PipeTwo::class => 'b',
                BrokenPipeOne::class => 'c',
            ])
            ->via('handle')
            ->then(function ($passable) {
                return $passable .= '(e)';
            })
            ->run();

        self::assertEquals('(-2b)(-1a)', $result);
    }

    /**
     * BypassPipe returns passable not calling $next() method.
     *
     * @throws PipelineException
     */
    public function testBypassPipe(): void
    {
        $passable = '(0)';
        $container = new Container();
        $pipeline = new Pipeline($container);

        $result = $pipeline
            ->send($passable)
            ->through([
                PipeOne::class => 'a',
                BypassPipe::class => 'c',
                PipeTwo::class => 'b',
            ])
            ->via('handle')
            ->then(function ($passable) {
                return $passable .= '(e)';
            })
            ->run();

        self::assertEquals('(0)(1a)(-1a)', $result);
    }

    /**
     * @throws PipelineException
     */
    public function testNoContainer(): void
    {
        $passable = '(0)';
        $pipeline = new Pipeline();

        $this->expectException(PipelineException::class);

        $pipeline
            ->send($passable)
            ->through([
                PipeOne::class,
                PipeTwo::class
            ])
            ->via('handle')
            ->run();
    }

    /**
     * @throws PipelineException
     */
    public function testNoContainerCreatedPipes(): void
    {
        $passable = '(0)';
        $pipeline = new Pipeline();

        $result = $pipeline
            ->send($passable)
            ->through([
                new PipeOne,
                new PipeTwo
            ])
            ->via('handle')
            ->run();

        self::assertEquals('(0)(1)(2)(-2)(-1)', $result);
    }
}

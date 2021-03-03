<?php
/*
 * This file is part of the OpxCore.
 *
 * Copyright (c) Lozovoy Vyacheslav <opxcore@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpxCore\Tests\Pipeline\Fixtures;


class PipeOne
{
    public function handle($passable, $next, $arg = null)
    {
        $passable .= "(1{$arg})";
        return $next($passable) . "(-1{$arg})";
    }
}
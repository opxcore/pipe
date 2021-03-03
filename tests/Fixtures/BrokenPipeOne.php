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


class BrokenPipeOne
{
    public function handle($passable, $next, $arg = null)
    {
        $passable .= "(3{$arg})";
        $next($passable) . "(-3{$arg})";
    }
}
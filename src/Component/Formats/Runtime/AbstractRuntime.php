<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Formats\Runtime;

abstract class AbstractRuntime
{
    /**
     * @param array $data
     */
    abstract public function __construct(array $data);
}

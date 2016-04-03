<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace RMF\Serferals\Component\ObjectBehavior;

/**
 * Class FactoryAwareObjectTrait
 */
trait FactoryAwareObjectTrait
{
    /**
     * @return static
     */
    final protected static function newInstance(...$parameters)
    {
        return new static(...$parameters);
    }
}

/* EOF */

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

use SR\Reflection\Inspect;
use SR\Reflection\Introspection\ObjectIntrospection;
use SR\Utility\ArrayUtil;

/**
 * Class PropertiesResettableObjectTrait
 */
trait PropertiesResettableObjectTrait
{
    /**
     * @param mixed[]    $mapping
     * @param null|mixed $default
     */
    protected function propertiesToMapping(array $mapping, $default = null)
    {
        if (ArrayUtil::isHash($mapping)) {
            $_ = function (\ReflectionProperty $p) use ($mapping, $default) {
                $p->setAccessible(true);
                $p->setValue($this, array_key_exists($p->getName(), $mapping) ? $mapping[$p->getName()] : $default);
            };
        } else {
            $_ = function (\ReflectionProperty $p) use ($mapping, $default) {
                static $i = 0;
                $p->setAccessible(true);
                $p->setValue($this, array_key_exists($i, $mapping) ? $mapping[$i] : $default);
                ++$i;
            };
        }

        $this->inspector()->visitProperties($_);
    }

    /**
     * @return $this
     */
    protected function propertiesToNull()
    {
        return $this->propertiesTo(null);
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    protected function propertiesTo($value)
    {
        $this->inspector()->visitProperties(function (\ReflectionProperty $p) use ($value) {
            $p->setAccessible(true);
            $p->setValue($this, $value);
        });

        return $this;
    }

    /**
     * @return ObjectIntrospection
     */
    abstract protected function inspector();
}

/* EOF */

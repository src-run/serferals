<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\ObjectBehavior;

use SR\Reflection\Introspection\ObjectIntrospection;
use SR\Reflection\Introspection\PropertyIntrospection;
use SR\Utility\ArrayUtil;

/**
 * Class PropertiesResettableObjectTrait.
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
            $_ = function (PropertyIntrospection $p) use ($mapping, $default) {
                $p->setValue($this, array_key_exists($p->getName(), $mapping) ? $mapping[$p->getName()] : $default);
            };
        } else {
            $_ = function (PropertyIntrospection $p) use ($mapping, $default) {
                static $i = 0;
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
        $this->inspector()->visitProperties(function (PropertyIntrospection $p) use ($value) {
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

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

use SR\Reflection\Inspector\ObjectInspector;
use SR\Reflection\Inspector\PropertyInspector;
use SR\Util\Info\ArrayInfo;

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
        if (ArrayInfo::isAssociative($mapping)) {
            $_ = function (PropertyInspector $p) use ($mapping, $default) {
                $p->setValue($this, array_key_exists($p->name(), $mapping) ? $mapping[$p->name()] : $default);
            };
        } else {
            $_ = function (PropertyInspector $p) use ($mapping, $default) {
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
        $this->inspector()->visitProperties(function (PropertyInspector $p) use ($value) {
            $p->setValue($this, $value);
        });

        return $this;
    }

    /**
     * @return ObjectInspector
     */
    abstract protected function inspector();
}

/* EOF */

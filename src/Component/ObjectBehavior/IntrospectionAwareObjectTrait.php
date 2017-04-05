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

use SR\Reflection\Inspect;
use SR\Serializer\Serializer;
use SR\Serializer\SerializerInterface;

/**
 * Class IntrospectionAwareObjectTrait.
 */
trait IntrospectionAwareObjectTrait
{
    /**
     * @return null|SerializerInterface
     */
    final protected function serializer()
    {
        static $serializer = null;

        if ($serializer === null) {
            $serializer = Serializer::create(SerializerInterface::TYPE_IGBINARY);
        }

        return $serializer;
    }

    /**
     * @return null|\SR\Reflection\Inspector\ObjectInspector
     */
    final protected function inspector()
    {
        static $inspector = null;

        if ($inspector === null) {
            $inspector = Inspect::useInstance($this);
        }

        return $inspector;
    }
}

/* EOF */

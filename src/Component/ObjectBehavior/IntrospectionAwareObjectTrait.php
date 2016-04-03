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
use SR\Wonka\Serializer\AbstractSerializer;
use SR\Wonka\Serializer\SerializerFactory;

/**
 * Class IntrospectionAwareObjectTrait
 */
trait IntrospectionAwareObjectTrait
{
    /**
     * @return AbstractSerializer
     */
    final protected function serializer()
    {
        static $serializer = null;

        if ($serializer === null) {
            $serializer = SerializerFactory::create(SerializerFactory::SERIALIZER_IGBINARY);
        }

        return $serializer;
    }

    /**
     * @return ObjectIntrospection
     */
    final protected function inspector()
    {
        static $inspector = null;

        if ($inspector === null) {
            $inspector = Inspect::thisInstance($this);
        }

        return $inspector;
    }
}

/* EOF */

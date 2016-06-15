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
use SR\Wonka\Serializer\AbstractSerializer;

/**
 * Class SerializableObjectTrait.
 */
trait SerializableObjectTrait
{
    /**
     * @return string
     */
    final public function serialize()
    {
        return static::serializer()->serializeData(
            $this->dataHibernate()
        );
    }

    /**
     * @param string $data
     */
    final public function unSerialize($data)
    {
        $this->dataHydrate(
            static::serializer()->unSerializeData($data)
        );
    }

    /**
     * @return mixed[]
     */
    protected function dataHibernate()
    {
        $data = [];

        $this->inspector()->visitProperties(function (PropertyIntrospection $p) use (&$data) {
            $data[$p->nameUnqualified()] = method_exists($this, 'dataHibernateVisitor') ?
                $this->dataHibernateVisitor($p->getValue($this), $p->nameUnqualified()) :
                $p->getValue($this);
        });

        return $data;
    }

    /**
     * @param mixed[] $data
     */
    protected function dataHydrate(array $data)
    {
        array_walk($data, function ($value, $name) {
            $this->{$name} = method_exists($this, 'dataHydrateVisitor') ?
                $this->dataHydrateVisitor($value, $name) :
                $value;
        });
    }

    /**
     * @return AbstractSerializer
     */
    abstract protected function serializer();

    /**
     * @return ObjectIntrospection
     */
    abstract protected function inspector();
}

/* EOF */

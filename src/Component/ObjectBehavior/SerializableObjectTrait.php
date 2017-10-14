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
use SR\Serializer\SerializerInterface;

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
        return static::serializer()->serialize(
            $this->dataHibernate()
        );
    }

    /**
     * @param string $data
     */
    final public function unSerialize($data)
    {
        $this->dataHydrate(
            static::serializer()->unserialize($data)
        );
    }

    /**
     * @return mixed[]
     */
    protected function dataHibernate()
    {
        $data = [];

        $this->inspector()->visitProperties(function (PropertyInspector $p) use (&$data) {
            $data[$p->nameUnqualified()] = method_exists($this, 'dataHibernateVisitor') ?
                $this->dataHibernateVisitor($p->value($this), $p->nameUnqualified()) :
                $p->value($this);
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
     * @return SerializerInterface
     */
    abstract protected function serializer();

    /**
     * @return ObjectInspector
     */
    abstract protected function inspector();
}


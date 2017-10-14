<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Model;

use SR\Spl\File\SplFileInfo as FileInfo;
use SR\Serferals\Component\ObjectBehavior\FactoryAwareObjectTrait;
use SR\Serferals\Component\ObjectBehavior\IntrospectionAwareObjectTrait;
use SR\Serferals\Component\ObjectBehavior\PropertiesResettableObjectTrait;
use SR\Serferals\Component\ObjectBehavior\SerializableObjectTrait;

trait MetadataModelTrait
{
    use FactoryAwareObjectTrait;
    use IntrospectionAwareObjectTrait;
    use PropertiesResettableObjectTrait;
    use SerializableObjectTrait;

    /**
     * @var FileInfo
     */
    protected $file;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @param bool $enabled
     */
    final public function __construct(bool $enabled = false)
    {
        $this->resetState($enabled);
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function resetState(bool $enabled = false)
    {
        $this->propertiesToNull();
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return FileInfo
     */
    public function getFile(): FileInfo
    {
        return $this->file;
    }

    /**
     * @param FileInfo $file
     *
     * @return $this
     */
    public function setFile(FileInfo $file)
    {
        $this->file = clone $file;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFileSize(): int
    {
        return $this->file->getSize();
    }

    /**
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->isEnabled();
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @param mixed  $value
     * @param string $name
     *
     * @return string|string[]
     */
    protected function dataHibernateVisitor($value, string $name)
    {
        if ($value instanceof FileInfo) {
            return [$value->getPathname(), $value->getRelativePath(), $value->getRelativePathname()];
        }

        return $value;
    }

    /**
     * @param mixed  $value
     * @param string $name
     *
     * @return mixed|string|FileInfo
     */
    protected function dataHydrateVisitor($value, string $name)
    {
        if ($name === 'file' && count($value) === 3) {
            return new FileInfo(...$value);
        }

        return $value;
    }
}


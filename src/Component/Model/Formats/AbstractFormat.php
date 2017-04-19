<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Model\Formats;

abstract class AbstractFormat
{
    /**
     * @var array
     */
    private $extensions;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $supported;

    /**
     * @param array  $extensions
     * @param string $name
     * @param bool   $supported
     */
    public function __construct(array $extensions, string $name, bool $supported)
    {
        $this->extensions = $extensions;
        $this->name = $name;
        $this->supported = $supported;
    }

    /**
     * @return string[]
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * @param string $extension
     *
     * @return bool
     */
    public function hasExtension(string $extension): bool
    {
        return in_array($extension, $this->extensions);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isSupported(): bool
    {
        return $this->supported;
    }
}

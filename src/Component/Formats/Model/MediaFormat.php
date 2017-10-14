<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Formats\Model;

abstract class MediaFormat extends AbstractFormat
{
    /**
     * @var array
     */
    private $extensions;

    /**
     * @var bool
     */
    private $supported;

    /**
     * @var bool
     */
    private $featured;

    /**
     * @param array  $extensions
     * @param bool   $featured
     * @param string $name
     * @param bool   $supported
     */
    public function __construct(array $extensions, bool $featured = false, string $name = null, bool $supported = true)
    {
        parent::__construct($name);

        $this->extensions = $extensions;
        $this->featured = $featured;
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
     * @return bool
     */
    public function isSupported(): bool
    {
        return $this->supported;
    }

    /**
     * @return bool
     */
    public function isFeatured(): bool
    {
        return $this->featured;
    }
}

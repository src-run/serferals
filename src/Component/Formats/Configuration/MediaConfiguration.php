<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Formats\Configuration;

use SR\Exception\Logic\InvalidArgumentException;
use SR\Serferals\Component\Formats\Model\AbstractFormat;
use SR\Serferals\Component\Formats\Model\SubtitleFormat;
use SR\Serferals\Component\Formats\Model\VideoFormat;

abstract class MediaConfiguration extends AbstractConfiguration
{
    /**
     * @param string $extension
     *
     * @return null|AbstractFormat|VideoFormat|SubtitleFormat
     */
    public function findOneByExtension(string $extension): ?AbstractFormat
    {
        if (1 < count($formats = $this->findAllByExtension($extension))) {
            throw new InvalidArgumentException('Multiple formats define extension "%s" so a single format cannot be returned!', $extension);
        }

        return 1 === count($formats) ? array_shift($formats) : null;
    }

    /**
     * @param string $extension
     *
     * @return AbstractFormat[]|VideoFormat[]|SubtitleFormat[]
     */
    public function findAllByExtension(string $extension): array
    {
        return $this->filterAll(function (AbstractFormat $format) use ($extension) {
            return method_exists($format, 'hasExtension') && $format->hasExtension($extension);
        });
    }

    /**
     * @return string[]
     */
    public function getExtensions(): array
    {
        return $this->getExtensionsFiltered(function () {
            return true;
        });
    }

    /**
     * @param \Closure $closure
     *
     * @return string[]
     */
    public function getExtensionsFiltered(\Closure $closure): array
    {
        $extensions = [];

        foreach ($this->filterEach($closure) as $f) {
            $extensions = array_merge($extensions, $f->getExtensions());
        }

        return array_unique($this->sortElementsNatural($extensions));
    }
}

<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Formats\Manager;

use SR\Serferals\Component\Formats\Model\AbstractFormat;
use SR\Serferals\Component\Formats\Configuration\SubtitleConfiguration;
use SR\Serferals\Component\Formats\Configuration\TargetConfiguration;
use SR\Serferals\Component\Formats\Configuration\VideoConfiguration;

class MediaManager extends AbstractManager
{
    /**
     * @return VideoConfiguration
     */
    public function getVideos(): VideoConfiguration
    {
        return $this->getConfiguration()->getVideos();
    }

    /**
     * @param bool $featured
     *
     * @return string[]
     */
    public function getVideoExtensions($featured = true): array
    {
        return $this->getFormatTypeExtensions('videos', $featured);
    }

    /**
     * @return SubtitleConfiguration
     */
    public function getSubtitles(): SubtitleConfiguration
    {
        return $this->getConfiguration()->getSubtitles();
    }

    /**
     * @param bool $featured
     *
     * @return string[]
     */
    public function getSubtitleExtensions($featured = true): array
    {
        return $this->getFormatTypeExtensions('subtitles', $featured);
    }

    /**
     * @return TargetConfiguration
     */
    public function getTargets(): TargetConfiguration
    {
        return $this->getConfiguration()->getTargets();
    }

    /**
     * @param string $type
     * @param bool   $featured
     *
     * @return string[]
     */
    private function getFormatTypeExtensions(string $type, bool $featured): array
    {
        return $this->getConfigurationType($type)->getExtensionsFiltered(function (AbstractFormat $format) use ($featured) {
            return $format->isFeatured() || !$featured;
        });
    }
}

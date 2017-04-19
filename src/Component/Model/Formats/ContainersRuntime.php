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

use SR\Exception\Logic\InvalidArgumentException;

class ContainersRuntime
{
    /**
     * @var VideoConfiguration
     */
    private $videos;

    /**
     * @var SubtitleConfiguration
     */
    private $subtitles;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (!isset($data['videos']) || !isset($data['subtitles'])) {
            throw new InvalidArgumentException('Containers configuration data missing "videos" or "subtitles" key');
        }

        $this->videos = new VideoConfiguration($data['videos']);
        $this->subtitles = new SubtitleConfiguration($data['subtitles']);
    }

    /**
     * @return VideoConfiguration
     */
    public function getVideos()
    {
        return $this->videos;
    }

    /**
     * @return SubtitleConfiguration
     */
    public function getSubtitles()
    {
        return $this->subtitles;
    }
}

<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Formats\Runtime;

use SR\Exception\Logic\InvalidArgumentException;
use SR\Serferals\Component\Formats\Configuration\SubtitleConfiguration;
use SR\Serferals\Component\Formats\Configuration\TargetConfiguration;
use SR\Serferals\Component\Formats\Configuration\VideoConfiguration;

class MediaRuntime extends AbstractRuntime
{
    /**
     * @var VideoConfiguration
     */
    private $videos = [];

    /**
     * @var SubtitleConfiguration
     */
    private $subtitles = [];

    /**
     * @var TargetConfiguration
     */
    private $targets = [];

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
        $this->targets = new TargetConfiguration($data['targets'] ?? []);
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

    /**
     * @return TargetConfiguration
     */
    public function getTargets()
    {
        return $this->targets;
    }
}

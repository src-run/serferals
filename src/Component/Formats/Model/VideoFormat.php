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

use SR\Serferals\Component\Formats\Configuration\VideoConfiguration;

class VideoFormat extends MediaFormat
{
    /**
     * @var string|null
     */
    private $transcode;

    /**
     * @param array       $extensions
     * @param bool        $featured
     * @param string      $name
     * @param bool        $supported
     * @param string|null $transcode
     */
    public function __construct(array $extensions, bool $featured, string $name, bool $supported, string $transcode = null)
    {
        parent::__construct($extensions, $featured, $name, $supported);

        $this->transcode = $transcode;
    }

    /**
     * @return bool
     */
    public function isTranscodeEnabled(): bool
    {
        return $this->transcode !== null;
    }

    /**
     * @return string
     */
    public function getTranscodeExtension(): string
    {
        return $this->transcode;
    }

    /**
     * @param VideoConfiguration $configuration
     *
     * @return null|VideoFormat
     */
    public function getTranscodeFormat(VideoConfiguration $configuration): ?VideoFormat
    {
        return $configuration->findOneByExtension($this->transcode);
    }
}

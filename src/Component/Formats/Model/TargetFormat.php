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

class TargetFormat extends MediaFormat
{
    /**
     * @var string
     */
    private $audioCodec;

    /**
     * @var int
     */
    private $audioVbr;

    /**
     * @var string
     */
    private $fileFormat;

    /**
     * @var string
     */
    private $transcoderExecutable;

    /**
     * @var bool
     */
    private $transcoderHardwareAcceleration;

    /**
     * @var int
     */
    private $transcoderThreads;

    /**
     * @var string
     */
    private $videoCodec;

    /**
     * @var string
     */
    private $videoCodecPreset;

    /**
     * @var string
     */
    private $videoProfileTitle;

    /**
     * @var string
     */
    private $videoProfileLevel;

    /**
     * @var int
     */
    private $videoQuantizerScale;

    /**
     * @param string   $audioCodec
     * @param int      $audioVbr
     * @param string[] $extensions
     * @param bool     $featured
     * @param string   $fileFormat
     * @param string   $name
     * @param bool     $supported
     * @param string   $transcoderExecutable
     * @param bool     $transcoderHardwareAcceleration
     * @param int      $transcoderThreads
     * @param string   $videoCodec
     * @param string   $videoCodecPreset
     * @param string   $videoProfileTitle
     * @param string   $videoProfileLevel
     * @param int      $videoQuantizerScale
     */
    public function __construct(string $audioCodec, int $audioVbr, array $extensions, bool $featured, string $fileFormat, string $name, bool $supported, string $transcoderExecutable, bool $transcoderHardwareAcceleration, int $transcoderThreads, string $videoCodec, string $videoCodecPreset, string $videoProfileTitle, string $videoProfileLevel, int $videoQuantizerScale)
    {
        parent::__construct($extensions, $featured, $name, $supported);

        $this->audioCodec = $audioCodec;
        $this->audioVbr = $audioVbr;
        $this->fileFormat = $fileFormat;
        $this->transcoderExecutable = $transcoderExecutable;
        $this->transcoderHardwareAcceleration = $transcoderHardwareAcceleration;
        $this->transcoderThreads = $transcoderThreads;
        $this->videoCodec = $videoCodec;
        $this->videoCodecPreset = $videoCodecPreset;
        $this->videoProfileTitle = $videoProfileTitle;
        $this->videoProfileLevel = $videoProfileLevel;
        $this->videoQuantizerScale = $videoQuantizerScale;
    }

    /**
     * @return string
     */
    public function getAudioCodec(): string
    {
        return $this->audioCodec;
    }

    /**
     * @return int
     */
    public function getAudioVbr(): int
    {
        return $this->audioVbr;
    }

    /**
     * @return string
     */
    public function getFileFormat(): string
    {
        return $this->fileFormat;
    }

    /**
     * @return string
     */
    public function getTranscoderExecutable(): string
    {
        return $this->transcoderExecutable;
    }

    /**
     * @return bool
     */
    public function isTranscoderHardwareAcceleration(): bool
    {
        return $this->transcoderHardwareAcceleration;
    }

    /**
     * @return int
     */
    public function getTranscoderThreads(): int
    {
        return $this->transcoderThreads;
    }

    /**
     * @return string
     */
    public function getVideoCodec(): string
    {
        return $this->videoCodec;
    }

    /**
     * @return string
     */
    public function getVideoCodecPreset(): string
    {
        return $this->videoCodecPreset;
    }

    /**
     * @return string
     */
    public function getVideoProfileTitle(): string
    {
        return $this->videoProfileTitle;
    }

    /**
     * @return string
     */
    public function getVideoProfileLevel(): string
    {
        return $this->videoProfileLevel;
    }

    /**
     * @return int
     */
    public function getVideoQuantizerScale(): int
    {
        return $this->videoQuantizerScale;
    }
}

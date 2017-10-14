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

use SR\Spl\File\SplFileInfo;

class FileMoveInstruction
{
    /**
     * @var SplFileInfo
     */
    private $origin;

    /**
     * @var SplFileInfo
     */
    private $output;

    /**
     * @var FileMoveInstruction|null
     */
    private $subtitle;

    /**
     * @param SplFileInfo        $origin
     * @param SplFileInfo        $output
     */
    public function __construct(SplFileInfo $origin, SplFileInfo $output, FileMoveInstruction $subtitle = null)
    {
        $this->origin = $origin;
        $this->output = $output;
        $this->subtitle = $subtitle;
    }

    /**
     * @return SplFileInfo
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @return SplFileInfo
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return null|FileMoveInstruction
     */
    public function getSubtitle(): ?FileMoveInstruction
    {
        return $this->subtitle;
    }

    /**
     * @return bool
     */
    public function hasSubtitle(): bool
    {
        return null !== $this->subtitle;
    }
}

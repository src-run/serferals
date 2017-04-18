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
     * @param SplFileInfo        $origin
     * @param SplFileInfo        $output
     */
    public function __construct(SplFileInfo $origin, SplFileInfo $output)
    {
        $this->origin = $origin;
        $this->output = $output;
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
}

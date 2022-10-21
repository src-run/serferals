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

use SR\Serferals\Component\Filesystem\PathDefinition;
use SR\Serferals\Component\Filesystem\PathDefinitionGroup;
use SR\Spl\File\SplFileInfo;

class FileMoveInstruction
{
    /**
     * @var PathDefinitionGroup
     */
    private $outputPathDefinitions;

    /**
     * @var FileMoveInstruction|null
     */
    private $subtitle;

    /**
     * @param PathDefinitionGroup      $outputPathDefinitions
     * @param FileMoveInstruction|null $subtitle
     */
    public function __construct(PathDefinitionGroup $outputPathDefinitions, FileMoveInstruction $subtitle = null)
    {
        $this->outputPathDefinitions = $outputPathDefinitions;
        $this->subtitle = $subtitle;
    }

    /**
     * @return PathDefinitionGroup
     */
    public function getOutputPathDefinitions(): PathDefinitionGroup
    {
        return $this->outputPathDefinitions;
    }

    /**
     * @return PathDefinition
     */
    public function getOriginDefinition(): PathDefinition
    {
        return $this->outputPathDefinitions->origin();
    }

    /**
     * @return SplFileInfo
     */
    public function getOrigin(): SplFileInfo
    {
        return $this->getOriginDefinition()->getPathCompiled();
    }

    /**
     * @return PathDefinition
     */
    public function getOutputDefinition(): PathDefinition
    {
        return $this->outputPathDefinitions->output();
    }

    /**
     * @return SplFileInfo
     */
    public function getOutput(): SplFileInfo
    {
        return $this->getOutputDefinition()->getPathCompiled();
    }

    /**
     * @return PathDefinition
     */
    public function getStagedDefinition(): PathDefinition
    {
        return $this->outputPathDefinitions->staged();
    }

    /**
     * @return SplFileInfo
     */
    public function getStaged(): SplFileInfo
    {
        return $this->getStagedDefinition()->getPathCompiled();
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

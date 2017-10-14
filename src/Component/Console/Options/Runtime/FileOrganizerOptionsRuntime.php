<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Console\Options\Runtime;

use SR\Console\Output\Style\StyleInterface;
use SR\Serferals\Component\Tasks\Filesystem\FileAtomicMoverTask;
use SR\Serferals\Component\Tasks\Metadata\FileMetadataTask;

class FileOrganizerOptionsRuntime extends OptionsRuntime
{
    /**
     * @var string[]
     */
    private $searchPaths;

    /**
     * @var string
     */
    private $outputPath;

    /**
     * @var string[]
     */
    private $searchMediaExt;

    /**
     * @var string[]
     */
    private $searchSubExt;

    /**
     * @var string[]
     */
    private $cleanFirstExt;

    /**
     * @var string[]
     */
    private $cleanAfterExt;

    /**
     * @var bool
     */
    private $actionModeMovies;

    /**
     * @var bool
     */
    private $actionModeEpisodes;

    /**
     * @var bool
     */
    private $overwriteBlind;

    /**
     * @var bool
     */
    private $overwriteSmart;

    /**
     * @var bool
     */
    private $failureSkipped;

    /**
     * @var bool
     */
    private $placedModeMove;

    /**
     * @var bool
     */
    private $placedModeCopy;

    /**
     * @var bool
     */
    private $subtitleAssociationsDisabled;

    /**
     * @param StyleInterface $style
     * @param string[]       $searchPaths
     * @param string         $outputPath
     * @param bool     $searchMediaAdd
     * @param string[] $searchMediaExt
     * @param string[] $searchMediaDef
     * @param bool     $searchSubAdd
     * @param string[] $searchSubExt
     * @param string[] $searchSubDef
     * @param bool     $cleanFirstAdd
     * @param string[] $cleanFirstExt
     * @param string[] $cleanFirstDef
     * @param bool     $cleanAfterAdd
     * @param string[] $cleanAfterExt
     * @param string[] $cleanAfterDef
     * @param bool     $actionModeMovies
     * @param bool     $actionModeEpisodes
     * @param bool     $blindOverwrite
     * @param bool     $smartOverwrite
     * @param bool     $failureSkipped
     * @param bool     $placedModeMove
     * @param bool     $placedModeCopy
     * @param bool     $subtitleAssociationsDisabled
     */
    public function __construct(StyleInterface $style, array $searchPaths, string $outputPath, bool $searchMediaAdd, array $searchMediaExt, array $searchMediaDef, bool $searchSubAdd, array $searchSubExt, array $searchSubDef, bool $cleanFirstAdd, array $cleanFirstExt, array $cleanFirstDef, bool $cleanAfterAdd, array $cleanAfterExt, array $cleanAfterDef, bool $actionModeMovies, bool $actionModeEpisodes, bool $blindOverwrite, bool $smartOverwrite, bool $failureSkipped, bool $placedModeMove, bool $placedModeCopy, bool $subtitleAssociationsDisabled)
    {
        parent::__construct($style);

        $this->searchPaths = $this->sanitizePaths($searchPaths, false);
        $this->outputPath = $this->sanitizePath($outputPath);

        if ($searchMediaAdd) {
            $searchMediaExt = array_merge($searchMediaExt, $searchMediaDef);
        }

        $this->searchMediaExt = $this->sanitizeExtensions($searchMediaExt);

        if ($searchSubAdd) {
            $searchSubExt = array_merge($searchSubExt, $searchSubDef);
        }

        $this->searchSubExt = $this->sanitizeExtensions($searchSubExt);

        if ($cleanFirstAdd) {
            $cleanFirstExt = array_merge($cleanFirstExt, $cleanFirstDef);
        }

        $this->cleanFirstExt = $this->sanitizeExtensions($cleanFirstExt);

        if ($cleanAfterAdd) {
            $cleanAfterExt = array_merge($cleanAfterExt, $cleanAfterDef);
        }

        $this->cleanAfterExt = $this->sanitizeExtensions($cleanAfterExt);

        if ($actionModeMovies && $actionModeEpisodes) {
            $this->writeNote('Both "force-episode" and "force-movie" modes cannot be simultaneously enabled; setting both options to "false".');
            $actionModeMovies = $actionModeEpisodes = false;
        }

        $this->actionModeMovies = $actionModeMovies;
        $this->actionModeEpisodes = $actionModeEpisodes;

        $this->overwriteBlind = $blindOverwrite;
        $this->overwriteSmart = $smartOverwrite;
        $this->failureSkipped = $failureSkipped;
        $this->placedModeMove = $placedModeMove;
        $this->placedModeCopy = $placedModeCopy;
        $this->subtitleAssociationsDisabled = $subtitleAssociationsDisabled;
    }

    /**
     * @return string[]
     */
    public function getSearchPaths(): array
    {
        return $this->searchPaths;
    }

    /**
     * @return string
     */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    /**
     * @return string[]
     */
    public function getSearchMediaExt(): array
    {
        return $this->searchMediaExt;
    }

    /**
     * @return string[]
     */
    public function getSearchSubExt(): array
    {
        return $this->searchSubExt;
    }

    /**
     * @return string[]
     */
    public function getCleanFirstExt(): array
    {
        return $this->cleanFirstExt;
    }

    /**
     * @return string[]
     */
    public function getCleanAfterExt(): array
    {
        return $this->cleanAfterExt;
    }

    /**
     * @return bool
     */
    public function isActionModeMovies(): bool
    {
        return $this->actionModeMovies;
    }

    /**
     * @return bool
     */
    public function isActionModeEpisodes(): bool
    {
        return $this->actionModeEpisodes;
    }

    /**
     * @return string
     */
    public function getActionModeType(): string
    {
        if ($this->isActionModeMovies()) {
            return FileMetadataTask::TYPE_MOVIE;
        }

        if ($this->isActionModeEpisodes()) {
            return FileMetadataTask::TYPE_EPISODE;
        }

        return FileMetadataTask::TYPE_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isOverwriteBlind(): bool
    {
        return $this->overwriteBlind;
    }

    /**
     * @return bool
     */
    public function isOverwriteSmart(): bool
    {
        return $this->overwriteSmart;
    }

    /**
     * @return bool
     */
    public function isFailureSkipped(): bool
    {
        return $this->failureSkipped;
    }

    /**
     * @return bool
     */
    public function isPlacedModeMove(): bool
    {
        return $this->placedModeMove;
    }

    /**
     * @return bool
     */
    public function isPlacedModeCopy(): bool
    {
        return $this->placedModeCopy;
    }

    /**
     * @return string
     */
    public function getPlacedModeType(): string
    {
        if ($this->isPlacedModeCopy()) {
            return FileAtomicMoverTask::MODE_CP;
        }

        if ($this->isPlacedModeMove()) {
            return FileAtomicMoverTask::MODE_MV;
        }

        return FileAtomicMoverTask::MODE_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isSubtitleAssociationsDisabled(): bool
    {
        return $this->subtitleAssociationsDisabled;
    }
}

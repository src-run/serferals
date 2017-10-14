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

class FileDeduplicatorOptionsRuntime extends OptionsRuntime
{
    /**
     * @var string[]
     */
    private $searchPaths;

    /**
     * @var string[]
     */
    private $ignoredExtensions;

    /**
     * @var string[]
     */
    private $ignoredFiles;

    /**
     * @var string[]
     */
    private $ignoredPaths;

    /**
     * @var bool
     */
    private $modeDryRun;

    /**
     * @var float
     */
    private $maxFileSize;

    /**
     * @param StyleInterface $style
     * @param string[]       $searchPaths
     * @param string[]       $ignoredFiles
     * @param string[]       $ignoredPaths
     * @param string[]       $ignoredExtensions
     * @param bool           $modeDryRun
     * @param float|null     $maxFileSize
     */
    public function __construct(StyleInterface $style, array $searchPaths, array $ignoredExtensions, array $ignoredFiles, array $ignoredPaths, bool $modeDryRun, float $maxFileSize = null)
    {
        parent::__construct($style);

        $this->searchPaths = $this->sanitizePaths($searchPaths);
        $this->ignoredExtensions = $this->sanitizeExtensions($ignoredExtensions);
        $this->ignoredFiles = $this->sanitizeIndexedArray($ignoredFiles);
        $this->ignoredPaths = $this->sanitizeIndexedArray($ignoredPaths);
        $this->modeDryRun = $modeDryRun;
        $this->maxFileSize = $this->sanitizeFileSize($maxFileSize);
    }

    /**
     * @return string[]
     */
    public function getSearchPaths(): array
    {
        return $this->searchPaths;
    }

    /**
     * @return string[]
     */
    public function getIgnoredExtensions(): array
    {
        return $this->ignoredExtensions;
    }

    /**
     * @return bool
     */
    public function hasIgnoredExtensions(): bool
    {
        return 0 !== count($this->ignoredExtensions);
    }

    /**
     * @return string[]
     */
    public function getIgnoredFiles(): array
    {
        return $this->ignoredFiles;
    }

    /**
     * @return bool
     */
    public function hasIgnoredFiles(): bool
    {
        return 0 !== count($this->ignoredFiles);
    }

    /**
     * @return string[]
     */
    public function getIgnoredPaths(): array
    {
        return $this->ignoredPaths;
    }

    /**
     * @return bool
     */
    public function hasIgnoredPaths(): bool
    {
        return 0 !== count($this->ignoredPaths);
    }

    /**
     * @return bool
     */
    public function isModeDryRun(): bool
    {
        return $this->modeDryRun;
    }

    /**
     * @return float|null
     */
    public function getMaxFileSize(): ?float
    {
        return $this->maxFileSize;
    }

    /**
     * @return bool
     */
    public function hasMaxFileSize(): bool
    {
        return null !== $this->maxFileSize;
    }

    /**
     * @return null|string
     */
    public function getMaxFileSizeFormatted(): ?string
    {
        if (!$this->hasMaxFileSize()) {
            return $this->getMaxFileSize();
        }

        $formats = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($this->maxFileSize) / log(1024);

        return trim(sprintf('%0.2f %s', pow(1024, $base - floor($base)), $formats[(int) floor($base)]));
    }
}

<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Utilities;

final class ProjectUtilities
{
    /**
     * @var string
     */
    private $projectRootPath;

    public function __construct()
    {
        $this->projectRootPath = $this->locateProjectPath();
    }

    /**
     * @return string
     */
    public function getProjectRootPath(): string
    {
        return $this->compilePathName($this->projectRootPath);
    }

    /**
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->compilePathName($this->getProjectRootPath(), 'app', 'config');
    }

    /**
     * @return string
     */
    public function getSourcePath(): string
    {
        return $this->compilePathName($this->getProjectRootPath(), 'src');
    }

    /**
     * @return string
     */
    public function getVendorPath(): string
    {
        return $this->compilePathName($this->getProjectRootPath(), 'vendor');
    }

    /**
     * @param string[] ...$parts
     *
     * @return string
     */
    public function compilePathName(string ...$parts): string
    {
        return $this->compileFilePath(...$parts).DIRECTORY_SEPARATOR;
    }

    /**
     * @param string[] ...$parts
     *
     * @return string
     */
    public function compileFilePath(string ...$parts): string
    {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * @return string
     */
    private function locateProjectPath(): string
    {
        $projectPath = $thisPath = dirname((new \ReflectionObject($this))->getFileName());

        while (!file_exists($this->compileFilePath($projectPath, 'composer.json'))) {
            if ($projectPath === dirname($projectPath)) {
                return $thisPath;
            }

            $projectPath = dirname($projectPath);
        }

        return $projectPath;
    }
}

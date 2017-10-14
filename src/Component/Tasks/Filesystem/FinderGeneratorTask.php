<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Tasks\Filesystem;

use SR\Console\Output\Style\StyleAwareTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\VarDumper\VarDumper;

class FinderGeneratorTask
{
    use StyleAwareTrait;

    /**
     * @var string[]
     */
    protected $paths = [];

    /**
     * @var string[]
     */
    protected $extensions = [];

    /**
     * @var string[]
     */
    protected $notPaths = [];

    /**
     * @var string[]
     */
    protected $notNames = [];

    /**
     * @var string[]
     */
    protected $notExtensions = [];

    /**
     * @return $this
     */
    public function reset(): self
    {
        $this->paths = [];
        $this->extensions = [];
        $this->notPaths = [];
        $this->notNames = [];
        $this->notExtensions = [];

        return $this;
    }

    /**
     * @param string[] ...$paths
     *
     * @return self
     */
    public function in(string ...$paths): self
    {
        return $this->paths(...$paths);
    }

    /**
     * @param string[] ...$paths
     *
     * @return $this
     */
    public function paths(string ...$paths): self
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * @param string[] ...$extensions
     *
     * @return $this
     */
    public function extensions(string ...$extensions): self
    {
        $this->extensions = $extensions;

        return $this;
    }

    /**
     * @param string[] ...$notPaths
     *
     * @return $this
     */
    public function notPaths(string ...$notPaths): self
    {
        $this->notPaths = $notPaths;

        return $this;
    }

    /**
     * @param string[] ...$notName
     *
     * @return $this
     */
    public function notNames(string ...$notName): self
    {
        $this->notNames = $notName;

        return $this;
    }

    /**
     * @param string[] ...$notExtensions
     *
     * @return $this
     */
    public function notExts(string ...$notExtensions): self
    {
        $this->notExtensions = $notExtensions;

        return $this;
    }

    /**
     * @return Finder
     */
    public function find(): Finder
    {
        $finder = Finder::create();
        $finder
            ->ignoreUnreadableDirs(true)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        foreach ($this->paths as $path) {
            $finder->in($path);
        }

        foreach ($this->extensions as $extension) {
            $finder->name('*.'.$extension);
        }

        foreach ($this->notPaths as $notPath) {
            $finder->notPath($notPath);
        }

        foreach ($this->notNames as $notName) {
            $finder->notName($notName);
        }

        foreach ($this->notExtensions as $notExtension) {
            $finder->notName(sprintf('*.%s', $notExtension));
        }

        return $finder->files();
    }
}

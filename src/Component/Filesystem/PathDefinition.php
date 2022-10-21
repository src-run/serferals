<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Filesystem;

use SR\Spl\File\SplFileInfo as FileInfo;

class PathDefinition
{
    /**
     * @var string
     */
    public const OBJECT_OUTPUT = 'output';

    /**
     * @var string
     */
    public const OBJECT_STAGED = 'staged';

    /**
     * @var string
     */
    public const OBJECT_ORIGIN = 'origin';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var FileInfo|null
     */
    private $pathCompiled;

    /**
     * @var int
     */
    private $perm;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $group;

    /**
     * @param string $name
     * @param string $path
     * @param int    $perm
     * @param string $user
     * @param string $group
     */
    public function __construct(string $name, string $path, int $perm, string $user, string $group) {
        $this->name = $name;
        $this->path = $path;
        $this->perm = $perm;
        $this->user = $user;
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return string|null
     */
    public function getRealPath(): ?string
    {
        return realpath($this->getPathCompiled()) ?: null;
    }

    /**
     * @return FileInfo|null
     */
    public function getPathCompiled(): ?FileInfo
    {
        return $this->pathCompiled ?? ($this->path ? new FileInfo($this->path) : null);
    }

    /**
     * @param FileInfo|null $pathCompiled
     *
     * @return void
     */
    public function setPathCompiled(?FileInfo $pathCompiled): void
    {
        $this->pathCompiled = $pathCompiled;
    }

    /**
     * @param string ...$additionalPaths
     *
     * @return $this
     */
    public function compilePathCompiled(string ...$additionalPaths): self
    {
        $this->pathCompiled = new FileInfo(
            preg_replace(
                sprintf('{[%s]+}', DIRECTORY_SEPARATOR),
                DIRECTORY_SEPARATOR,
                join(DIRECTORY_SEPARATOR, array_merge([$this->path], $additionalPaths))
            )
        );

        return $this;
    }

    /**
     * @return bool
     */
    public function hadPathCompiled(): bool
    {
        return null !== $this->pathCompiled;
    }

    /**
     * @return int
     */
    public function getPerm(): int
    {
        return $this->perm;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }
}

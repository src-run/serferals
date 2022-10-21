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

use SR\Console\Output\Style\StyleAwareTrait;
use SR\Exception\Logic\InvalidArgumentException;
use SR\Serferals\Component\Console\StdIO\StdIOTrait;

class PathDefinitionGroup
{
    use StyleAwareTrait;
    use StdIOTrait;

    /**
     * @var PathDefinition[]
     */
    private $fileDefinitions = [];

    /**
     * @param PathDefinition ...$filePermissions
     */
    public function __construct(PathDefinition ...$filePermissions) {
        $this->addFilePermissionDefinitions(...$filePermissions);
    }

    /**
     * @param PathDefinition ...$filePermissions
     *
     * @return void
     */
    public function addFilePermissionDefinitions(PathDefinition ...$filePermissions): void
    {
        $definitions = array_combine(array_map(function (PathDefinition $definition) {
            return $definition->getName();
        }, $filePermissions), $filePermissions);

        $this->fileDefinitions = array_merge($this->fileDefinitions, $definitions);
    }

    /**
     * @param string $name
     *
     * @return PathDefinition
     */
    public function get(string $name): PathDefinition
    {
        if (array_key_exists($name, $this->fileDefinitions)) {
            return $this->fileDefinitions[$name];
        }

        throw new InvalidArgumentException('Invalid path permissions definition requested: "%s"...', $name);
    }

    /**
     * @return PathDefinition
     */
    public function output(): PathDefinition
    {
        return $this->get(PathDefinition::OBJECT_OUTPUT);
    }

    /**
     * @return PathDefinition
     */
    public function staged(): PathDefinition
    {
        return $this->get(PathDefinition::OBJECT_STAGED);
    }

    /**
     * @return PathDefinition
     */
    public function origin(): PathDefinition
    {
        return $this->get(PathDefinition::OBJECT_ORIGIN);
    }

    /**
     * @param bool $ensureWriteable
     * @param bool $ensureReadable
     *
     * @return $this
     */
    public function sanitizeDefinitionPaths(bool $ensureWriteable = true, bool $ensureReadable = true): self
    {
        foreach ($this->fileDefinitions as $definition) {
            $this->sanitizePath($definition, $ensureWriteable, $ensureReadable);
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function ensureDirectoryPathExists(string $name): bool
    {
        $o = $this->get($name);
        $d = new PathDefinition(
            $o->getName(),
            $o->getPathCompiled()->getPath(),
            $o->getPerm(),
            $o->getUser(),
            $o->getGroup()
        );

        if (!$this->createPath($d)) {
            $this->io->error(sprintf('Could not create directory "%s"', $d->getRealPath()));
            return false;
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function ensureFileOwnership(string $name): bool
    {
        $d = $this->get($name);

        if (!$this->assignPathPermissions($d)) {
            $this->io->error(sprintf('Could not create directory "%s"', $d->getRealPath()));
            return false;
        }

        return true;
    }

    /**
     * @param PathDefinition $definition
     * @param bool           $ensureWriteable
     * @param bool           $ensureReadable
     *
     * @return void
     */
    private function sanitizePath(PathDefinition $definition, bool $ensureWriteable = true, bool $ensureReadable = true): void
    {
        if (null === $definition->getRealPath()) {
            $this->writeWarning('Provided path "%s" does not exist', $definition->getPathCompiled());

            if (!$this->io->confirm('Attempt to create directory?') || !$this->createPath($definition)) {
                $this->writeHaltingError('Exiting due to non-existent path that could not be created!');
            }
        }

        if ($ensureReadable && false === is_readable($definition->getRealPath())) {
            $this->writeHaltingError(sprintf('Provided path "%s" is not readable', $definition->getRealPath()));
        }

        if ($ensureWriteable && false === is_writable($definition->getRealPath())) {
            $this->writeHaltingError(sprintf('Provided path "%s" is not writable', $definition->getRealPath()));
        }
    }

    /**
     * @param PathDefinition $definition
     *
     * @return bool
     */
    private function createPath(PathDefinition $definition): bool
    {
        if (!is_dir($definition->getRealPath()) && !mkdir($definition->getPathCompiled(), $definition->getPerm(), true)) {
            return false;
        }

        return $this->assignPathPermissions($definition);
    }

    /**
     * @param PathDefinition $definition
     *
     * @return bool
     */
    private function assignPathPermissions(PathDefinition $definition): bool
    {
        if (!$definition->getRealPath()) {
            return false;
        }

        if (!chmod($definition->getRealPath(), $definition->getPerm())) {
            return false;
        }

        if (!chown($definition->getRealPath(), $definition->getUser())) {
            return false;
        }

        if (!chgrp($definition->getRealPath(), $definition->getGroup())) {
            return false;
        }

        return true;
    }
}

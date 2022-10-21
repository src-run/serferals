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

use SR\Console\Output\Style\StyleAwareTrait;
use SR\Console\Output\Style\StyleInterface;
use SR\Serferals\Component\Console\StdIO\StdIOTrait;

abstract class OptionsRuntime
{
    use StyleAwareTrait;
    use StdIOTrait;

    /**
     * @var string
     */
    private const SANITIZE_EXTENSION_REGEX = '^\.?(?<extension>[a-z0-9]+)$';

    /**
     * @param StyleInterface $style
     */
    public function __construct(StyleInterface $style)
    {
        $this->setStyle($style);
    }

    /**
     * @param array $collection
     *
     * @return array
     */
    protected function sanitizeIndexedArray(array $collection): array
    {
        return array_values($collection);
    }

    /**
     * @param string[] $paths
     * @param bool     $ensureWriteable
     * @param bool     $ensureReadable
     *
     * @return string[]
     */
    protected function sanitizePaths(array $paths, bool $ensureWriteable = true, bool $ensureReadable = true): array
    {
        return array_values(array_map(function (string $path) use ($ensureWriteable, $ensureReadable) {
            return $this->sanitizePath($path, $ensureWriteable, $ensureReadable);
        }, $paths));
    }

    /**
     * @param string $path
     * @param bool   $ensureWriteable
     * @param bool   $ensureReadable
     *
     * @return string
     */
    protected function sanitizePath(string $path, bool $ensureWriteable = true, bool $ensureReadable = true): string
    {
        if (false === ($real = realpath($path))) {
            $this->writeHaltingError('Provided path "%s" does not exist', $path);
        }

        if ($ensureReadable && false === is_readable($real)) {
            $this->writeHaltingError(sprintf('Provided path "%s" is not readable', $real));
        }

        if ($ensureWriteable && false === is_writable($real)) {
            $this->writeHaltingError(sprintf('Provided path "%s" is not writable', $real));
        }

        return $real;
    }

    /**
     * @param string[] $extensions
     *
     * @return string[]
     */
    protected function sanitizeExtensions(array $extensions): array
    {
        return array_values(array_unique(array_map(function (string $extension) {
            return $this->sanitizeExtension($extension);
        }, $extensions)));
    }

    /**
     * @param string $extension
     *
     * @return string
     */
    protected function sanitizeExtension(string $extension): string
    {
        if (1 !== preg_match(sprintf('{%s}i', self::SANITIZE_EXTENSION_REGEX), $extension, $matches)) {
            $this->writeHaltingError(sprintf('Provided extension "%s" is invalid (must follow regexp %s)', $extension, self::SANITIZE_EXTENSION_REGEX));
        }

        return $matches['extension'];
    }

    /**
     * @param string|null $string
     * @param bool        $allowNull
     *
     * @return float|null
     */
    protected function sanitizeFileSize(string $string = null, bool $allowNull = true): ?float
    {
        $formats = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        if (1 !== preg_match(sprintf('{^(?<size>[0-9]+(\.[0-9]+)?)\s*?(?<type>%s)$}', implode('|', $formats)), $string, $matches)) {
            if ($allowNull && $string === null) {
                return null;
            }

            $this->writeHaltingError(sprintf('The size provided "%s" contains an invalid type (valid types are %s)', $string, implode(', ', $formats)));
        }

        $type = $matches['type'];
        $size = $matches['size'];

        switch ($type) {
            case 'B':
                break;

            case 'KB':
                $size *= 1024;
                break;

            default;
                $size *= pow(1024, array_search($type, $formats));
                break;
        }

        return $size;
    }
}

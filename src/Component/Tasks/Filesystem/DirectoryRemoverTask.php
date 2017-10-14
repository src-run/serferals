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
use SR\Console\Output\Style\StyleInterface;

class DirectoryRemoverTask
{
    use StyleAwareTrait;

    /**
     * @param string[] $paths
     * @param string[] ...$extensions
     */
    public function run(array $paths, ...$extensions)
    {
        $this->io
            ->environment(StyleInterface::VERBOSITY_VERBOSE)
            ->subSection('Post-Task Operations');

        $deletions = 0;

        foreach ($paths as $p) {
            $this->removePath($p, $extensions, $deletions, true);
        }

        $this->io
            ->environment(StyleInterface::VERBOSITY_VERBOSE)
            ->info(sprintf('Removed "%d" files matching "*.(%s)" pattern.', $deletions, implode('|', $extensions)));
   }

    /**
     * @param string $folder
     * @param array  $extensions
     * @param int    $deletions
     * @param bool   $root
     *
     * @return bool
     */
    private function removePath($folder, array $extensions, &$deletions, $root = false)
    {
        $inner = array_filter(scandir($folder), function ($i) {
            return $i != '.' && $i != '..';
        });
        $inner = array_map(function ($i) use ($folder) {
            return $folder.DIRECTORY_SEPARATOR.$i;
        }, $inner);
        $count = count($inner);

        foreach ($inner as $i) {
            if (is_dir($i) && $this->removePath($i, $extensions, $deletions, false)) {
                --$count;
                continue;
            }

            if (!in_array(pathinfo($i, PATHINFO_EXTENSION), $extensions)) {
                continue;
            }

            if (false === @unlink($i)) {
                $this->io->comment(sprintf('<em>Error removing</em> <comment>%s</comment>', $i));
            } else {
                ++$deletions;
                --$count;
                $this->io
                    ->environment(StyleInterface::VERBOSITY_VERY_VERBOSE)
                    ->comment(sprintf('Removing <comment>%s</comment>', $i));
            }
        }

        if ($count !== 0 || $root !== false) {
            return false;
        }

        if (false === @rmdir($folder)) {
            $this->io
                ->environment(StyleInterface::VERBOSITY_VERBOSE)
                ->comment(sprintf('Error removing <comment>%s</comment>', $folder));

            return false;
        }

        ++$deletions;


        $this->io
            ->environment(StyleInterface::VERBOSITY_VERY_VERBOSE)
            ->comment(sprintf('Removing <comment>%s</comment>', $folder));

        return true;
    }
}

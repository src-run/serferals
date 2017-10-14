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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ExtensionRemoverTask
{
    use StyleAwareTrait;

    /**
     * @param string[] $paths
     * @param string[] $extensions
     */
    public function run(array $paths, array $extensions)
    {
        $this->io->environment(StyleInterface::VERBOSITY_VERBOSE)
            ->subSection('Cleanup Operations');

        if (0 === count($extensions)) {
            $this->io
                ->environment(StyleInterface::VERBOSITY_VERBOSE)
                ->info(sprintf('Removed "0" files (no extension patterns defined).', implode('|', $extensions)));

            return;
        }

        $finder = Finder::create();

        foreach ($paths as $in) {
            $finder->in($in);
        }

        foreach ($extensions as $e) {
            $finder->name('*.'.$e);
        }

        $finder->files();
        $count = $finder->count();

        foreach ($finder as $file) {
            $this->delete($file);
        }

        $this->io->environment(StyleInterface::VERBOSITY_VERBOSE)
            ->info(sprintf('Removed "%d" files matching "*.(%s)" pattern.', $count, implode('|', $extensions)));
    }

    /**
     * @param SplFileInfo $file
     */
    private function delete(SplFileInfo $file)
    {
        if (!unlink($file->getPathname())) {
            $this->io->error('Could not remove '.$file->getPathname());
        }
    }
}

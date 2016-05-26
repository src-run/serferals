<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Operation;

use SR\Console\Style\StyleAwareTrait;
use SR\Console\Style\StyleInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class RemoveDirOperation
 */
class RemoveDirOperation
{
    use StyleAwareTrait;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @param string[]    $ins
     * @param string[] ...$extensions
     */
    public function run(array $ins, ...$extensions)
    {
        $deletions = 0;

        foreach ($ins as $in) {
            $this->removePath($in, $extensions, $deletions, true);
        }

        $this->ioVerbose(function(StyleInterface $io) use ($in, $deletions) {
            $io->comment(sprintf('Found <info>%d</info> files/paths for cleanup in input path(s)', $deletions));
        });
    }

    /**
     * @param string $folder
     * @param array  $extensions
     * @param int    $deletions
     * @param bool   $root
     */
    private function removePath($folder, array $extensions, &$deletions, $root = false)
    {
        $inner = array_filter(scandir($folder), function ($i) {
            return $i != '.' && $i != '..';
        });
        $inner = array_map(function ($i) use ($folder) {
            return $folder . DIRECTORY_SEPARATOR . $i;
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
                $this->io->comment(sprintf('<em>Error removing</em> <comment>%s</comment>', $i), false);
            } else {
                ++$deletions;
                --$count;
                $this->ioVeryVerbose(function (StyleInterface $io) use ($f) {
                    $io->comment(sprintf('Removing <comment>%s</comment>', $i), false);
                });
            }
        }

        if ($count !== 0 || $root !== false) {
            return false;
        }

        if (false === @rmdir($folder)) {
            $this->ioVerbose(function (StyleInterface $io) use ($folder) {
                $io->comment(sprintf('Error removing <comment>%s</comment>', $folder), false);
            });

            return false;
        }

        ++$deletions;

        $this->ioVeryVerbose(function (StyleInterface $io) use ($folder) {
            $io->comment(sprintf('Removing <comment>%s</comment>', $folder), false);
        });

        return true;
    }
}

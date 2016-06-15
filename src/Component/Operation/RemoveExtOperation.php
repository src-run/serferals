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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class RemoveExtOperation.
 */
class RemoveExtOperation
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
     * @param string[] $ins
     * @param string[] ...$extensions
     */
    public function run(array $ins, ...$extensions)
    {
        $finder = Finder::create();

        foreach ($ins as $in) {
            $finder->in($in);
        }

        $finder->files();

        foreach ($extensions as $e) {
            $finder->name('*.'.$e);
        }

        $this->ioVerbose(function (StyleInterface $io) use ($finder, $extensions) {
            $io->comment(sprintf(
                'Found <info>%d</info> files matching <info>*.(%s)</info> for removal',
                $finder->count(), implode('|', $extensions)), false);
        });

        foreach ($finder as $file) {
            $this->ioVeryVerbose(function (StyleInterface $io) use ($file) {
                $io->comment(sprintf(
                    'Removing <comment>%s</comment>',
                    $file->getPathname()), false);
            });
            $this->delete($file);
        }
    }

    /**
     * @param SplFileInfo $file
     */
    private function delete(SplFileInfo $file)
    {
        if (!unlink($file->getPathname())) {
            $this->io()->error('Could not remove '.$file->getPathname());
        }
    }
}

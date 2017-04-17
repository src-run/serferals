<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Command;

use SR\Console\Style\StyleInterface;
use SR\Serferals\Component\Operation\ApiLookupOperation;
use SR\Serferals\Component\Operation\PathScanOperation;
use SR\Serferals\Component\Operation\RemoveDirOperation;
use SR\Serferals\Component\Operation\RemoveExtOperation;
use SR\Serferals\Component\Operation\RenameOperation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class DuplicatesCommand.
 */
class DuplicatesCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('dups')
            ->setDescription('Search for duplicate files.')
            ->addUsage('an/input/path/to/search')
            ->setHelp('Scan input directory for media files and resolve duplicate items.')
            ->setDefinition([
                new InputOption('ext', ['x'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Input extensions to consider media.', ['mov', 'mkv', 'mp4', 'avi']),
                new InputArgument('input-dirs', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Path to read input files from.'),
            ]);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->ioSetup($input, $output);

        $this->checkRequirements();

        $this->io()->applicationTitle(
            strtoupper($this->getApplication()->getName()),
            $this->getApplication()->getVersion(),
            $this->getApplication()->getGitHash(), [
                'Author' => sprintf('%s <%s>', $this->getApplication()->getAuthor(), $this->getApplication()->getAuthorEmail()),
                'License' => $this->getApplication()->getLicense(),
            ]
        );

        $inputExtensions = $input->getOption('ext');
        list($inputPaths, $inputInvalidPaths) = $this->validatePaths(true, ...$input->getArgument('input-dirs'));

        if (count($inputInvalidPaths) > 0 || !(count($inputPaths) > 0)) {
            $this->io()->error('You must provide at least one valid input path.');

            return 255;
        }

        $this->showRuntimeConfiguration($inputPaths, $inputExtensions);

        $scanner = $this->operationPathScan();

        $lookup = $this->operationApiLookup();
        $finder = $scanner
            ->paths(...$inputPaths)
            ->extensions(...$inputExtensions)
            ->find();

        $parser = $lookup->getFileResolver();
        $itemCollection = $parser
            ->setFinder($finder)
            ->getItems();
    }

    /**
     * @param string[] $inputPaths
     * @param string[] $inputExtensions
     */
    private function showRuntimeConfiguration(array $inputPaths, array $inputExtensions)
    {
        $tableRows = [];

        foreach ($inputPaths as $i => $path) {
            $tableRows[] = ['Search Directory (#'.($i + 1).')', $path];
        }

        $tableRows[] = ['Search Extension List', implode(',', $inputExtensions)];

        $this->ioVerbose(
            function (StyleInterface $io) use ($tableRows) {
                $io->subSection('Runtime Configuration');
                $io->table([], $tableRows);
            }
        );

        $this->ioDebug(
            function () {
                if (false === $this->io()->confirm('Continue using these values?', true)) {
                    exit(1);
                }
            }
        );
    }

    /**
     * @return RenameOperation
     */
    private function getServiceRename()
    {
        return $this->getService('sr.serferals.operation_rename');
    }

    /**
     * @return ApiLookupOperation
     */
    private function operationApiLookup()
    {
        return $this->getService('sr.serferals.operation_api_lookup');
    }

    /**
     * @return PathScanOperation
     */
    private function operationPathScan()
    {
        return $this->getService('sr.serferals.operation_path_scan');
    }

    /**
     * @return RemoveExtOperation
     */
    private function operationRemoveExts()
    {
        return $this->getService('sr.serferals.operation_remove_ext');
    }

    /**
     * @return RemoveDirOperation
     */
    private function operationRemoveDirs()
    {
        return $this->getService('sr.serferals.operation_remove_dir');
    }
}

/* EOF */

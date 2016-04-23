<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace RMF\Serferals\Command;

use RMF\Serferals\Component\Console\InputOutputAwareTrait;
use RMF\Serferals\Component\Console\Style\StyleInterface;
use RMF\Serferals\Component\Operation\RemoveExtsOperation;
use RMF\Serferals\Component\Operation\ApiLookupOperation;
use RMF\Serferals\Component\Operation\RenamerOperation;
use RMF\Serferals\Component\Operation\PathScanOperation;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ScanCommand
 */
class ScanCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('scan')
            ->setDescription('Scan media file queue and organize.')
            ->addUsage('--type=ep --type=movie a/path/for/input/files')
            ->setHelp('Scan input directory for media files, resolve episode/movie metadata, rename and output using proper directory structure and file names.')
            ->setDefinition([
                new InputOption('type', ['t'], InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'Input media type.', ['supported']),
                new InputOption('task', ['r'], InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'Run additional tasks.', ['clean']),
                new InputOption('remove', null, InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'Input paths remove files with exts.', ['txt', 'nfo']),
                new InputOption('ext', ['x'], InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'Input extensions to consider media.', ['mov', 'mkv', 'mp4', 'avi']),
                new InputOption('output-dir', ['o'], InputOption::VALUE_REQUIRED, 'Path to output to.'),
                new InputOption('overwrite', ['w'], InputOption::VALUE_NONE, 'Overwrite output files if exist.'),
                new InputArgument('input-dirs', InputArgument::IS_ARRAY|InputArgument::REQUIRED, 'Path to read input files from.')
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

        $this->io()->applicationTitle(
            $this->getApplication()->getName(),
            $this->getApplication()->getVersion(),
            ['by', 'Rob Frawley 2nd <rmf@src.run>']);

        $this->io()->comment(sprintf('Running command <info>%s</info>', 'scan'));

        $inputExtensions = $input->getOption('ext');
        $cleanExtentions = $input->getOption('remove');
        list($inputPaths, $inputInvalidPaths) = $this->validatePaths(true, ...$input->getArgument('input-dirs'));
        list($outputPath, $outputInvalidPath) = $this->validatePaths(false, $input->getOption('output-dir'));

        if ($outputInvalidPath) {
            $this->io()->error('Invalid output path: '.$outputInvalidPath);
            return 255;
        }

        if (!$outputPath) {
            $this->io()->error('You must provide an output directory.');
            return 255;
        }

        if (count($inputInvalidPaths) !== 0) {
            $this->io()->error('Invalid input path(s): '.implode(', ', $inputInvalidPaths));
            return 255;
        }

        $this->showRuntimeConfiguration($outputPath, $inputPaths, $cleanExtentions, $inputExtensions);
        $this->doPreScanTasks($inputPaths, $cleanExtentions);
        $scanner = $this->operationPathScan();

        $lookup = $this->operationApiLookup();
        $finder = $scanner
            ->paths(...$inputPaths)
            ->extensions(...$inputExtensions)
            ->find();

        $parser = $lookup->getFileResolver();
        $itemCollection = $parser
            ->using($finder)
            ->getItems();

        $this->ioV(function() use ($itemCollection) {
            $this->io()->comment('Found '.count($itemCollection).' media files for parsing.');
        });

        $itemCollection = $lookup->resolve($itemCollection);

        $renamer = $this->operationReNamer();
        $renamer->run($outputPath, $itemCollection, $input->getOption('overwrite'));

        $this->io()->success('Done');

        return 0;
    }

    /**
     * @param string   $outputPath
     * @param string[] $inputPaths
     * @param string[] $cleanExtentions
     * @param string[] $inputExtensions
     */
    private function showRuntimeConfiguration($outputPath, array $inputPaths, array $cleanExtentions, array $inputExtensions)
    {
        $tableRows = [];

        foreach ($inputPaths as $i => $path) {
            $tableRows[] = ['Search Directory (#'.($i+1).')', $path];
        }

        $tableRows[] = ['Output Directory', $outputPath];
        $tableRows[] = ['Search Extension List', implode(',', $inputExtensions)];

        if ($this->io()->isVeryVerbose()) {
            $tableRows[] = ['Remove Extension List', implode(',', $cleanExtentions)];
        }

        $this->ioV(function (StyleInterface $io) use ($tableRows) {
            $io->comment('Listing runtime configuration');
            $io->table([], $tableRows);
        });

        $this->ioVV(function () {
            if (false === $this->io()->confirm('Continue using these values?', true)) {
                $this->endError();
            }
        });
        
        $this->ioN(function (StyleInterface $io) use ($outputPath, $inputExtensions, $inputPaths) {
            $io->comment(
                sprintf(
                    'Filtering files by <info>*.(%s)</info>',
                    implode('|', $inputExtensions)
                ),
                false
            );

            $io->comment(
                sprintf(
                    'within path(s) <info>%s</info>',
                    implode('|', $inputPaths)
                ),
                false
            );

            $io->comment(
                sprintf(
                    'with output base path <info>%s</info>',
                    $outputPath
                ),
                false
            );
        });
    }

    private function doPreScanTasks(array $inputPaths, $cleanExtentions)
    {
        $deleteExtensions = $this->operationRemoveExts();
        $deleteExtensions->run($inputPaths, ...$cleanExtentions);
    }

    /**
     * @return RenamerOperation
     */
    private function operationReNamer()
    {
        return $this->getService('rmf.serferals.operation_renamer');
    }

    /**
     * @return ApiLookupOperation
     */
    private function operationApiLookup()
    {
        return $this->getService('rmf.serferals.operation_api_lookup');
    }

    /**
     * @return PathScanOperation
     */
    private function operationPathScan()
    {
        return $this->getService('rmf.serferals.operation_path_scan');
    }

    /**
     * @return RemoveExtsOperation
     */
    private function operationRemoveExts()
    {
        return $this->getService('rmf.serferals.operation_remove_exts');
    }
}

/* EOF */

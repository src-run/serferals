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
use RMF\Serferals\Component\Operation\DeleteExtensionsOperation;
use RMF\Serferals\Component\Operation\LookupResolverOperation;
use RMF\Serferals\Component\Operation\ParseFileNamesOperation;
use RMF\Serferals\Component\Operation\RenamerOperation;
use RMF\Serferals\Component\Operation\ScanInputsOperation;
use RMF\Serferals\Component\Queue\QueueEpisodeItem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ScanCommand
 */
class ScanCommand extends Command
{
    use InputOutputAwareTrait;

    /**
     * @var StyleInterface
     */
    protected $style;

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
                new InputArgument('input-dirs', InputArgument::IS_ARRAY|InputArgument::REQUIRED, 'Path to read input files from.')
            ]);
    }

    /**
     * @param InputInterface $input
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
        $scanner = $this->serviceScanInputPaths();

        $finder = $scanner
            ->paths(...$inputPaths)
            ->extensions(...$inputExtensions)
            ->find();

        $parser = $this->serviceFileNameParser();
        $itemCollection = $parser
            ->using($finder)
            ->getItems();

        $this->ioV(function() use ($itemCollection) {
            $this->io()->comment('Found '.count($itemCollection).' media files in search path(s).');
        });

        $lookup = $this->serviceLookupResolver();
        $itemCollection = $lookup->resolve($itemCollection);

        $renamer = $this->serviceRenamer();
        $renamer->run($outputPath, $itemCollection);

        $this->io()->comment('Complete');

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
            $io->section('Runtime Settings');
            $io->text('<comment>Using the following configuration values:</comment>');
            $io->table([], $tableRows);
        });

        $this->ioVV(function () {
            if (false === $this->io()->confirm('Continue using these values?', true)) {
                $this->endError();
            }
        });
        
        $this->ioN(function (StyleInterface $io) use ($outputPath, $inputExtensions, $inputPaths) {
            $io->text('Filtering filenames by <info>'.implode('|', $inputExtensions).'</info> within '.
                '<info>'.implode('|', $inputPaths).'</info> with destination <info>'.$outputPath.'</info>.');
        });
    }

    private function doPreScanTasks(array $inputPaths, $cleanExtentions)
    {
        $deleteExtensions = $this->serviceDeleteByExtensions();
        $deleteExtensions->run($inputPaths, ...$cleanExtentions);
    }

    private function queueItem(QueueEpisodeItem $item)
    {
        $this->io()->writeln($item->name);
    }

    /**
     * @return RenamerOperation
     */
    private function serviceRenamer()
    {
        return $this->getService('rmf.serferals.operation_renamer');
    }

    /**
     * @return LookupResolverOperation
     */
    private function serviceLookupResolver()
    {
        return $this->getService('rmf.serferals.operation_lookup_resolver');
    }

    /**
     * @return ParseFileNamesOperation
     */
    private function serviceFileNameParser()
    {
        return $this->getService('rmf.serferals.operation_parse_file_names');
    }

    /**
     * @return ScanInputsOperation
     */
    private function serviceScanInputPaths()
    {
        return $this->getService('rmf.serferals.operation_scan_inputs');
    }

    /**
     * @return DeleteExtensionsOperation
     */
    private function serviceDeleteByExtensions()
    {
        return $this->getService('rmf.serferals.operation_delete_extensions');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function ioSetup(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input);
        $this->setOutput($output);
        $this->setStyle($this->getService('rmf.serferals.console_style'));
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    private function getService($name)
    {
        return $this
            ->getApplication()
            ->getContainer()
            ->get($name);
    }

    /**
     * @param bool                $returnMultiple
     * @param string|string[],... $paths
     *
     * @return array[]
     */
    private function validatePaths($returnMultiple = true, ...$paths)
    {
        $valid = [];
        $invalid = [];

        foreach ($paths as $p) {
            if (false !== ($r = realpath($p)) && is_readable($p) && is_writable($p)) {
                $valid[] = $r;
            } else {
                $invalid[] = $p;
            }
        }

        if ($returnMultiple === false) {
            $valid = array_pop($valid);
            $invalid = array_pop($invalid);
        }

        return [ $valid, $invalid ];
    }

    private function endError()
    {
        $this->io()->error('Exiting script prior to completion!');
        exit(255);
    }
}

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
    /**
     * @var string[]
     */
    private $taskExtsAsMedia;

    /**
     * @var string[]
     */
    private $taskExtsRemovePre;

    /**
     * @var string[]
     */
    private $taskExtsRemovePost;

    /**
     * @param string[]      $taskExtsAsMedia
     * @param null|string[] $taskExtsRemovePre
     * @param null|string[] $taskExtsRemovePost
     */
    public function __construct($taskExtsAsMedia, $taskExtsRemovePre = null, $taskExtsRemovePost = null)
    {
        $this->taskExtsAsMedia = $taskExtsAsMedia;
        $this->taskExtsRemovePre = $taskExtsRemovePre;
        $this->taskExtsRemovePost = $taskExtsRemovePost;

        parent::__construct();
    }

    /**
     * configure command name, desc, usage, help, options, etc
     */
    protected function configure()
    {
        $this
            ->setName('scan')
            ->setDescription('Scan media file queue and organize.')
            ->addUsage('-tTf -e avi -e mkv -o /output/dir/path a/path/for/input/files')
            ->setHelp('Scan input directory for media files, resolve episode/movie metadata, rename and output using proper directory structure and file names.')
            ->setDefinition([
                new InputOption('ext', ['e'], InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'File extensions understood to be media files.', $this->taskExtsAsMedia),
                new InputOption('overwrite', ['f'], InputOption::VALUE_NONE, 'Force media file overwrite (replace) if same file already exists.'),
                new InputOption('output-path', ['o'], InputOption::VALUE_REQUIRED, 'Output directory to write organized media to.'),
                new InputOption('pre-task', ['t'], InputOption::VALUE_NONE, 'Enable pre-scan file/dir cleaning and other tasks.'),
                new InputOption('post-task', ['T'], InputOption::VALUE_NONE, 'Enable post-scan file/dir cleaning and other tasks.'),
                new InputOption('pre-ext', ['x'], InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'File extensions to remove during pre-scan task runs.', $this->taskExtsRemovePre),
                new InputOption('post-ext', ['X'], InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'File extensions to remove during post-scan task runs.', $this->taskExtsRemovePost),
                new InputArgument('input-path', InputArgument::IS_ARRAY|InputArgument::REQUIRED, 'Input directory path(s) to read unorganized media from.')
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

        $this->io()->comment(sprintf('Running command <comment>%s</comment>', 'scan'));

        $cleanPreTask = $input->getOption('pre-task');
        $cleanPostTask = $input->getOption('post-task');
        $cleanExtensionsPre = $input->getOption('pre-ext');
        $cleanExtensionsPost = $input->getOption('post-ext');

        $inputExtensions = $input->getOption('ext');
        list($inputPaths, $inputInvalidPaths) = $this->validatePaths(true, ...$input->getArgument('input-path'));
        list($outputPath, $outputInvalidPath) = $this->validatePaths(false, $input->getOption('output-path'));

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

        $this->showRuntimeConfiguration($outputPath, $inputPaths, array_unique(array_merge($cleanExtensionsPre, $cleanExtensionsPost)), $inputExtensions);

        if ($cleanPreTask) {
            $this->doPreRunTasks($inputPaths, $cleanExtensionsPre);
        }

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
            $this->io()->comment(sprintf('Found <info>%d</info> media files in input path(s)', count($itemCollection)));
        });

        $itemCollection = $lookup->resolve($itemCollection);

        $renamer = $this->operationReNamer();
        $renamer->run($outputPath, $itemCollection, $input->getOption('overwrite'));

        if ($cleanPostTask) {
            $this->doPostRunTasks($inputPaths, $cleanExtensionsPost);
        }

        $this->io()->success('Done');

        return 0;
    }

    /**
     * @param string   $outputPath
     * @param string[] $inputPaths
     * @param string[] $cleanExtensions
     * @param string[] $inputExtensions
     */
    private function showRuntimeConfiguration($outputPath, array $inputPaths, array $cleanExtensions, array $inputExtensions)
    {
        $tableRows = [];

        foreach ($inputPaths as $i => $path) {
            $tableRows[] = ['Search Directory (#'.($i+1).')', $path];
        }

        $tableRows[] = ['Output Directory', $outputPath];
        $tableRows[] = ['Search Extension List', implode(',', $inputExtensions)];
        $tableRows[] = ['Remove Extension List', implode(',', $cleanExtensions)];

        $this->ioV(function (StyleInterface $io) use ($tableRows) {
            $io->comment('Listing runtime configuration');
            $io->table([], $tableRows);
        });

        $this->ioVVV(function () {
            if (false === $this->io()->confirm('Continue using these values?', true)) {
                $this->endError();
            }
        });
    }

    /**
     * @param string[] $inputPaths
     * @param string[] $extensions
     */
    private function doPreRunTasks(array $inputPaths, $extensions)
    {
        $deleteExtensions = $this->operationRemoveExts();
        $deleteExtensions->run($inputPaths, ...$extensions);
    }

    /**
     * @param string[] $inputPaths
     * @param string[] $extensions
     */
    private function doPostRunTasks(array $inputPaths, $extensions)
    {
        $deleteExtensions = $this->operationRemoveExts();
        $deleteExtensions->run($inputPaths, ...$extensions);

        $deleteDirectories = $this->operationRemoveDirs();
        $deleteDirectories->run($inputPaths);
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

    /**
     * @return RemoveDirsOperation
     */
    private function operationRemoveDirs()
    {
        return $this->getService('rmf.serferals.operation_remove_dirs');
    }
}

/* EOF */

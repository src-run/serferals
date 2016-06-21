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
use SR\Serferals\Component\Operation\RemoveDirOperation;
use SR\Serferals\Component\Operation\RemoveExtOperation;
use SR\Serferals\Component\Operation\ApiLookupOperation;
use SR\Serferals\Component\Operation\RenameOperation;
use SR\Serferals\Component\Operation\PathScanOperation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ScanCommand.
 */
class ScanCommand extends AbstractCommand
{
    /**
     * @var string[]
     */
    private $extAsMedia;

    /**
     * @var string[]
     */
    private $extToRemovePre;

    /**
     * @var string[]
     */
    private $extToRemovePost;

    /**
     * @param string[]      $extMedia
     * @param null|string[] $extRemovePre
     * @param null|string[] $extRemovePost
     */
    public function __construct($extMedia, $extRemovePre = null, $extRemovePost = null)
    {
        $this->extAsMedia = $extMedia;
        $this->extToRemovePre = $extRemovePre;
        $this->extToRemovePost = $extRemovePost;

        parent::__construct();
    }

    /**
     * configure command name, desc, usage, help, options, etc.
     */
    protected function configure()
    {
        $this
            ->setName('scan')
            ->setDescription('Scan media file queue and organize.')
            ->setHelp('Scan input directory for media files, resolve episode/movie metadata, rename and output using proper directory structure and file names.')
            ->setDefinition([
                new InputOption('ext', ['e'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'File extensions understood to be media files.', $this->extAsMedia),
                new InputOption('overwrite', ['f'], InputOption::VALUE_NONE, 'Force media path overwrite if output already exists.'),
                new InputOption('smart-overwrite', ['s'], InputOption::VALUE_NONE, 'Force media path overwrite if output already exists and input is larger.'),
                new InputOption('output-path', ['o'], InputOption::VALUE_REQUIRED, 'Output directory to write organized media to.'),
                new InputOption('pre-task', ['t'], InputOption::VALUE_NONE, 'Enable pre-scan file/dir cleaning and other tasks.'),
                new InputOption('post-task', ['T'], InputOption::VALUE_NONE, 'Enable post-scan file/dir cleaning and other tasks.'),
                new InputOption('skip-lookup-failure', ['S'], InputOption::VALUE_NONE, 'Skip all files that fail API lookup.'),
                new InputOption('mode-tv', ['E'], InputOption::VALUE_NONE, 'Set mode explicitly to TV; skip movie matches.'),
                new InputOption('auto', ['A'], InputOption::VALUE_NONE, 'Enable auto mode.'),
                new InputOption('pre-ext', ['x'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'File extensions to remove during pre-scan task runs.', $this->extToRemovePre),
                new InputOption('post-ext', ['X'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'File extensions to remove during post-scan task runs.', $this->extToRemovePost),
                new InputArgument('input-path', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Input directory path(s) to read unorganized media from.', [getcwd()]),
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
            strtoupper($this->getApplication()->getName()),
            $this->getApplication()->getVersion(),
            $this->getApplication()->getGitHash(), [
                'Author' => sprintf('%s <%s>', $this->getApplication()->getAuthor(), $this->getApplication()->getAuthorEmail()),
                'License' => $this->getApplication()->getLicense(),
            ]
        );

        $cleanPreTask = $input->getOption('pre-task');
        $cleanPostTask = $input->getOption('post-task');
        $cleanExtensionsPre = $input->getOption('pre-ext');
        $cleanExtensionsPost = $input->getOption('post-ext');
        $modeEpisode = $input->getOption('mode-tv');
        $modeAuto = $input->getOption('auto');

        $inputExtensions = $input->getOption('ext');
        list($inputPaths, $inputInvalidPaths) = $this->validatePaths(true, ...$input->getArgument('input-path'));
        list($outputPath, $outputInvalidPath) = $this->validatePaths(false, $input->getOption('output-path'));

        if (count($inputInvalidPaths) > 0 || !(count($inputPaths) > 0)) {
            $this->io()->error('You must provide at least one valid input path.');

            return 255;
        }

        if ($outputInvalidPath) {
            $this->io()->error('You must provide a valid output path. (Invalid: '.$outputInvalidPath.')');

            return 255;
        }

        if (!$outputPath) {
            $this->io()->error('You must provide a valid output path.');

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
            ->getItems($modeEpisode);

        $itemCollection = $lookup->resolve($itemCollection, $input->getOption('skip-lookup-failure'), $modeAuto);

        $rename = $this->getServiceRename();
        $rename->run($outputPath, $itemCollection, $input->getOption('overwrite'), $input->getOption('smart-overwrite'));

        if ($cleanPostTask) {
            $this->doPostRunTasks($inputPaths, $cleanExtensionsPost);
        }

        $this->io()->smallSuccess('OK', 'Done');

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
            $tableRows[] = ['Search Directory (#'.($i + 1).')', $path];
        }

        $tableRows[] = ['Output Directory', $outputPath];
        $tableRows[] = ['Search Extension List', implode(',', $inputExtensions)];
        $tableRows[] = ['Remove Extension List', implode(',', $cleanExtensions)];

        $this->ioVerbose(function (StyleInterface $io) use ($tableRows) {
            $io->subSection('Runtime Configuration');
            $io->table($tableRows);
        });

        $this->ioDebug(function () {
            if (false === $this->io()->confirm('Continue using these values?', true)) {
                exit(1);
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

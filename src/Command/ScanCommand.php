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
    private $removeExtsFirst;

    /**
     * @var string[]
     */
    private $removeExtsAfter;

    /**
     * @param string[]      $extMedia
     * @param null|string[] $removeExtsFirst
     * @param null|string[] $removeExtsAfter
     */
    public function __construct($extMedia, $removeExtsFirst = null, $removeExtsAfter = null)
    {
        $this->extAsMedia = $extMedia;
        $this->removeExtsFirst = $removeExtsFirst;
        $this->removeExtsAfter = $removeExtsAfter;

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
                new InputOption('search-exts', ['e'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Extension(s) to be interpreted as media files.', $this->extAsMedia),
                new InputOption('blind-overwrite', ['f'], InputOption::VALUE_NONE, 'Overwrite existing file paths blindly if already exists.'),
                new InputOption('smart-overwrite', ['s'], InputOption::VALUE_NONE, 'Overwrite existing file paths if already exists and larger than existing file.'),
                new InputOption('output-path', ['o'], InputOption::VALUE_REQUIRED, 'Base destination (output) path to write to.'),
                new InputOption('skip-failures', ['S'], InputOption::VALUE_NONE, 'Automatically skip over any files that fail API lookups.'),
                new InputOption('force-episode', ['E'], InputOption::VALUE_NONE, 'Only organize episodes; all other input types ignored.'),
                new InputOption('force-movie', ['M'], InputOption::VALUE_NONE, 'Only organize movies; all other input types ignored.'),
                new InputOption('remove-exts-first', ['x'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Extensions to remove first (before) main organizational scan.', $this->removeExtsFirst),
                new InputOption('remove-exts-after', ['X'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Extensions to remove after main organizational scan.', $this->removeExtsAfter),
                new InputOption('copy', ['c'], InputOption::VALUE_NONE, 'Copy the input file (instead of moving it) to the destination path.'),
                new InputArgument('search-paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Input paths to search for media files.', [getcwd()]),
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

        if ($input->getOption('force-episode') === true && $input->getOption('force-movie')) {
            return $this->returnError(255, 'Cannot set mode to both episodes and movies. Select one or the other.');
        }

        list($inputPaths, $inputInvalidPaths) = $this
            ->validatePaths(true, ...$input->getArgument('search-paths'));
        list($outputPath, $outputInvalidPath) = $this
            ->validatePaths(false, $input->getOption('output-path'));

        if (true === (count($inputInvalidPaths) > 0) || true === (count($inputPaths) === 0)) {
            return $this->returnError(255, 'You must provide at least one valid input path.');
        }

        if ($outputInvalidPath) {
            return $this->returnError(255, 'You must provide a valid output path. (Invalid: %s)', $outputInvalidPath);
        }

        if (!$outputPath) {
            return $this->returnError(255, 'You must provide a valid output path.');
        }

        if (count($inputInvalidPaths) !== 0) {
            return $this->returnError(255, 'Invalid input path(s): %s', implode(', ', $inputInvalidPaths));
        }

        $this->writeRuntime($inputPaths, $outputPath, $input);
        $this->doFirstTasks($inputPaths, $input->getOption('remove-exts-first'));
        $this->runCoreTasks($inputPaths, $input->getOption('search-exts'), $outputPath, $input);
        $this->doAfterTasks($inputPaths, $input->getOption('remove-exts-after'));

        $this->io()->smallSuccess('OK', 'Done');

        return 0;
    }

    /**
     * @param int     $code
     * @param string  $message
     * @param mixed[] ...$replacements
     *
     * @return int
     */
    private function returnError(int $code, string $message, ...$replacements)
    {
        $this->io()->error(vsprintf($message, $replacements));

        return $code;
    }

    /**
     * @param string[]       $searchPaths
     * @param string[]       $searchExts
     * @param string         $destination
     * @param InputInterface $input
     */
    private function runCoreTasks(array $searchPaths, array $searchExts, string $destination, InputInterface $input)
    {
        $lookup = $this->operationApiLookup();
        $resolv = $lookup->getFileResolver();
        $finder = $this
            ->operationPathScan()
            ->paths(...$searchPaths)
            ->extensions(...$searchExts)
            ->find();

        $files = $resolv
            ->setFinder($finder)
            ->setForcedEpisode($input->getOption('force-episode'))
            ->setForcedMovie($input->getOption('force-movie'))
            ->getItems();

        $files = $lookup
            ->setSkipFailures($input->getOption('skip-failures'))
            ->resolve($files);

        $this
            ->getServiceRename()
            ->setOutputPath($destination)
            ->setBlindOverwrite($input->getOption('overwrite'))
            ->setSmartOverwrite($input->getOption('smart-overwrite'))
            ->setMode($input->getOption('copy') ? RenameOperation::MODE_CP : RenameOperation::MODE_MV)
            ->run($files);
    }

    /**
     * @param string[]       $inputPaths
     * @param string         $outputPath
     * @param InputInterface $input
     */
    private function writeRuntime(array $inputPaths, string $outputPath, InputInterface $input)
    {
        //             array_unique(array_merge($removeExtsFirst, $removeExtsAfter)),
//        $inputExtensions

        $implodeList = function (array $list, $glue = ', ') {
            return implode($glue, $list);
        };

        $tableRows = [];

        foreach ($inputPaths as $i => $path) {
            $tableRows[] = ['Search Directory (#'.($i + 1).')', $path];
        }

        $tableRows[] = ['Output Directory', $outputPath];
        $tableRows[] = ['Search Extensions', $implodeList($input->getOption('search-exts'))];
        $tableRows[] = ['Remove Extensions First', $implodeList($input->getOption('remove-exts-first'))];
        $tableRows[] = ['Remove Extensions After', $implodeList($input->getOption('remove-exts-after'))];
        $tableRows[] = ['Forced Mode', $input->getOption('force-episode') ? 'Episodes Only' :
            $input->getOption('force-movie') ? 'Movies Only' : 'None'];
        $tableRows[] = ['Skip Lookup Failures?', $input->getOption('skip-failures') ? 'Yes' : 'No'];
        $tableRows[] = ['Smart Overwrite Enabled?', $input->getOption('smart-overwrite') ? 'Yes' : 'No'];
        $tableRows[] = ['Blind Overwrite Enabled?', $input->getOption('blind-overwrite') ? 'Yes' : 'No'];

        $this->ioVerbose(function (StyleInterface $io) use ($tableRows) {
            $io->subSection('Runtime Configuration');
            $io->table([], $tableRows);
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
    private function doFirstTasks(array $inputPaths, $extensions)
    {
        $this->operationRemoveExts()->run($inputPaths, ...$extensions);
    }

    /**
     * @param string[] $inputPaths
     * @param string[] $extensions
     */
    private function doAfterTasks(array $inputPaths, $extensions)
    {
        $this->operationRemoveExts()->run($inputPaths, ...$extensions);
        $this->operationRemoveDirs()->run($inputPaths);
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

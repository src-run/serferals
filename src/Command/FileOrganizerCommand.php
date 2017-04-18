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
use SR\Dumper\YamlDumper;
use SR\Serferals\Component\Tasks\Filesystem\DirectoryRemoverTask;
use SR\Serferals\Component\Tasks\Filesystem\ExtensionRemoverTask;
use SR\Serferals\Component\Tasks\Filesystem\FileAtomicMoverTask;
use SR\Serferals\Component\Tasks\Filesystem\FileInstructionTask;
use SR\Serferals\Component\Tasks\Filesystem\FinderGeneratorTask;
use SR\Serferals\Component\Tasks\Metadata\FileMetadataTask;
use SR\Serferals\Component\Tasks\Metadata\TmdbMetadataTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class FileOrganizerCommand extends AbstractCommand
{
    /**
     * @var
     */
    private $containers;

    /**
     * @var string[]
     */
    private $extensions;

    /**
     * @var string[]
     */
    private $extensionsRemoveFirst;

    /**
     * @var string[]
     */
    private $extensionsRemoveAfter;

    /**
     * @var string
     */
    private $defaultDestinationPath;

    /**
     * @var FinderGeneratorTask
     */
    private $finderGenerator;

    /**
     * @var FileMetadataTask
     */
    private $fileMetadata;

    /**
     * @var TmdbMetadataTask
     */
    private $tmdbMetadata;

    /**
     * @var ExtensionRemoverTask
     */
    private $extensionRemover;

    /**
     * @var DirectoryRemoverTask
     */
    private $directoryRemover;

    /**
     * @var FileInstructionTask
     */
    private $fileInstruction;

    /**
     * @var FileAtomicMoverTask
     */
    private $fileAtomicMover;

    /**
     * @param string      $containerConfigurations
     * @param null|string $defaultDestinationPath
     */
    public function __construct(string $containerConfigurations, string $defaultDestinationPath = null)
    {
        $this->containers = $this->compileContainerConfigurations($containerConfigurations);
        $this->defaultDestinationPath = $defaultDestinationPath;

        parent::__construct();
    }

    /**
     * @param string[] $extensionsRemoveFirst
     * @param string[] $extensionsRemoveAfter
     */
    public function setRemoveExtensions(array $extensionsRemoveFirst, array $extensionsRemoveAfter)
    {
        $this->extensionsRemoveFirst = $extensionsRemoveFirst;
        $this->extensionsRemoveAfter = $extensionsRemoveAfter;
    }

    /**
     * @param FinderGeneratorTask $finderGenerator
     */
    public function setFinderGenerator(FinderGeneratorTask $finderGenerator)
    {
        $this->finderGenerator = $finderGenerator;
    }

    /**
     * @param FileMetadataTask $fileMetadata
     */
    public function setFileMetadata(FileMetadataTask $fileMetadata)
    {
        $this->fileMetadata = $fileMetadata;
    }

    /**
     * @param TmdbMetadataTask $tmdbMetadata
     */
    public function setTmdbMetadata(TmdbMetadataTask $tmdbMetadata)
    {
        $this->tmdbMetadata = $tmdbMetadata;
    }

    /**
     * @param ExtensionRemoverTask $extensionRemover
     */
    public function setExtensionRemover(ExtensionRemoverTask $extensionRemover)
    {
        $this->extensionRemover = $extensionRemover;
    }

    /**
     * @param DirectoryRemoverTask $directoryRemover
     */
    public function setDirectoryRemover(DirectoryRemoverTask $directoryRemover)
    {
        $this->directoryRemover = $directoryRemover;
    }

    /**
     * @param FileInstructionTask $fileInstruction
     */
    public function setFileInstruction(FileInstructionTask $fileInstruction)
    {
        $this->fileInstruction = $fileInstruction;
    }

    /**
     * @param FileAtomicMoverTask $fileAtomicMover
     */
    public function setFileAtomicMover(FileAtomicMoverTask $fileAtomicMover)
    {
        $this->fileAtomicMover = $fileAtomicMover;
    }

    /**
     * configure command name, desc, usage, help, options, etc.
     */
    protected function configure()
    {
        $this
            ->setName('scan')
            ->setDescription('Scan input directories for media files so their metadata can be retrieved and they can be moved into an organized folder structure.')
            ->setHelp('Scan input directory for media files, resolve episode/movie metadata, rename and output using proper directory structure and file names.')
            ->setDefinition([
                new InputOption('extension-add', [], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                    'Add media file extension to recognize.'),

                new InputOption('extension-rm-first', [], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                    'Extensions to remove first organizational scan', $this->extensionsRemoveFirst),

                new InputOption('extension-rm-after', [], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                    'Extensions to remove after organizational scan', $this->extensionsRemoveAfter),

                new InputOption('force-episode', ['E'], InputOption::VALUE_NONE,
                    'Ignore media files not recognized as <em>episodes</em>'),

                new InputOption('force-movie', ['M'], InputOption::VALUE_NONE,
                    'Ignore media files not recognized as <em>movies</em>'),

                new InputOption('overwrite-blind', ['b'], InputOption::VALUE_NONE,
                    'Overwrite existing files blindly if they already exist'),

                new InputOption('overwrite-smart', ['s'], InputOption::VALUE_NONE,
                    'Overwrite existing file if they are smaller than new one'),

                new InputOption('output-path', ['o'], InputOption::VALUE_REQUIRED,
                    'Base destination (output) path to write to', $this->defaultDestinationPath),

                new InputOption('skip-failures', ['f'], InputOption::VALUE_NONE,
                    'Ignore media files that fail metadata lookup'),

                new InputOption('use-copy', ['c'], InputOption::VALUE_NONE,
                    'Use the <em>copy</em> command to place media into output path <fg=yellow>[default: "disabled"]</>'),

                new InputOption('use-move', ['m'], InputOption::VALUE_NONE,
                    'Use the <em>move</em> command to place media into output path <fg=yellow>[default: "enabled"]</>'),

                new InputArgument('search-paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                    'Input paths to search for media files', [getcwd()]),
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
        //$this->ioSetup($input, $output);

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
        $this->doFirstTasks($inputPaths, $input->getOption('extension-rm-first'));
        $this->runCoreTasks($inputPaths, $input->getOption('extension-add'), $outputPath, $input);
        $this->doAfterTasks($inputPaths, $input->getOption('extension-rm-after'));

        $this->io()->smallSuccess('OK', 'Done');

        return 0;
    }

    private function compileContainerConfigurations(string $containerConfigurations)
    {
        $phar = 'phar://serferals.phar/'.$containerConfigurations;
        $file = vsprintf('%s%s..%s..%s%s', [
            __DIR__,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            $containerConfigurations,
        ]);

        $dumper = new YamlDumper(file_exists($phar) ? $phar : $file, new \DateInterval('P1M'));

        var_dump($dumper->dump());
        die('DONE');
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
     * @param string[]       $searchExtensions
     * @param string         $destination
     * @param InputInterface $input
     */
    private function runCoreTasks(array $searchPaths, array $searchExtensions, string $destination, InputInterface $input)
    {
        $files = $this->fileMetadata
            ->setFinder($this->finderGenerator->paths(...$searchPaths)->extensions(...$searchExtensions)->find())
            ->setForcedEpisode($input->getOption('force-episode'))
            ->setForcedMovie($input->getOption('force-movie'))
            ->execute();

        $files = $this->tmdbMetadata
            ->setSkipFailures($input->getOption('skip-failures'))
            ->resolve($files);

        $instructions = $this->fileInstruction
            ->setOutputPath($destination)
            ->execute($files);

        $this->fileAtomicMover
            ->setMode($input->getOption('copy') ? FileAtomicMoverTask::MODE_CP : FileAtomicMoverTask::MODE_MV)
            ->setBlindOverwrite($input->getOption('overwrite-blind'))
            ->setSmartOverwrite($input->getOption('overwrite-smart'))
            ->setFileMoveInstructions(...$instructions)
            ->execute();
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
        $tableRows[] = ['Search Extensions', $implodeList($input->getOption('extension-add'))];
        $tableRows[] = ['Remove Extensions First', $implodeList($input->getOption('extension-rm-first'))];
        $tableRows[] = ['Remove Extensions After', $implodeList($input->getOption('extension-rm-after'))];
        $tableRows[] = ['Forced Mode', $input->getOption('force-episode') ? 'Episodes Only' :
            $input->getOption('force-movie') ? 'Movies Only' : 'None'];
        $tableRows[] = ['Skip Lookup Failures?', $input->getOption('skip-failures') ? 'Yes' : 'No'];
        $tableRows[] = ['Smart Overwrite Enabled?', $input->getOption('overwrite-smart') ? 'Yes' : 'No'];
        $tableRows[] = ['Blind Overwrite Enabled?', $input->getOption('overwrite-blind') ? 'Yes' : 'No'];

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
    private function doFirstTasks(array $inputPaths, $extensions)
    {
        $this->extensionRemover->run($inputPaths, ...$extensions);
    }

    /**
     * @param string[] $inputPaths
     * @param string[] $extensions
     */
    private function doAfterTasks(array $inputPaths, $extensions)
    {
        $this->extensionRemover->run($inputPaths, ...$extensions);
        $this->directoryRemover->run($inputPaths);
    }
}

/* EOF */

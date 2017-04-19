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
use SR\Serferals\Component\Console\InputOutput;
use SR\Serferals\Component\Format\ContainersManager;
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
     * @var ContainersManager
     */
    private $containersManager;

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
     * @param null|string $defaultDestinationPath
     */
    public function __construct(string $defaultDestinationPath = null)
    {
        $this->defaultDestinationPath = $defaultDestinationPath;

        parent::__construct();
    }

    /**
     * @param ContainersManager $containersManager
     */
    public function setContainersManager(ContainersManager $containersManager)
    {
        $this->containersManager = $containersManager;
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

                new InputArgument('search-paths', InputArgument::IS_ARRAY,
                    'Input paths to search for media files'),
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
        $this->io->writeTitle($this->getApplication());

        list($originPaths, $outputPath) = $this->parseOptions($input);

        $this->writeRuntime($originPaths, $outputPath);
        //$this->doFirstTasks($originPaths, $input->getOption('extension-rm-first'));
        //$this->runCoreTasks($originPaths, $input->getOption('extension-add'), $outputPath, $input);
        //$this->doAfterTasks($originPaths, $input->getOption('extension-rm-after'));

        return $this->io->writeExit('All operations completed successfully');
    }

    /**
     * @param InputInterface $in
     *
     * @return array
     */
    private function parseOptions(InputInterface $in): array
    {
        if ($in->getOption('force-episode') && $in->getOption('force-movie')) {
            $this->io
                ->enterState(OutputInterface::VERBOSITY_VERY_VERBOSE)
                ->writeNotice('Ignoring forced "episode" and "move" mode as enabling both cancels the other out');

            $in->setOption('force-episode', false);
            $in->setOption('force-movie', false);
        }

        $origin = $this->sanitizePaths($in->getArgument('search-paths'));
        $output = $this->sanitizePath($in->getOption('output-path'));

        if (0 === count($origin)) {
            $this->io->writeCritical('At least one search path must be specified');
        }

        return [$origin, $output];
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
     * @param string[] $origin
     * @param string   $output
     */
    private function writeRuntime(array $origin, string $output)
    {
        $in = $this->io->getInput();
        $ar = [];

        foreach ($origin as $i => $path) {
            $ar[] = ['Search Directory (#'.($i + 1).')', $path];
        }
        $ar[] = ['Output Directory',        $output];
        $ar[] = ['Skip Lookup Failure',     $this->markupStateToggle($in->getOption('skip-failures')) ];
        $ar[] = ['Smart Overwrite',         $this->markupStateToggle($in->getOption('overwrite-smart'))];
        $ar[] = ['Blind Overwrite',         $this->markupStateToggle($in->getOption('overwrite-blind'))];
        $ar[] = ['Search Extensions',       $this->arrayToString($this->getSearchExtensions())];
        $ar[] = ['Remove Extensions First', $this->arrayToString($this->getRmFirstExtensions())];
        $ar[] = ['Remove Extensions After', $this->arrayToString($this->getRmAfterExtensions())];
        if ($in->getOption('force-episode')) {
            $ar[] = ['Forced Mode', 'Episodes'];
        } else if ($in->getOption('force-movie')) {
            $ar[] = ['Forced Mode', 'Movies'];
        }

        $this->io
            ->enterState(OutputInterface::VERBOSITY_VERBOSE)
            ->writeSectionHeader('Runtime Configuration')
            ->writeTableRows($ar);

        $this->io
            ->enterState(OutputInterface::VERBOSITY_DEBUG)
            ->askConfirm('Continue using this configuration?', true, function() {
                exit($this->io->writeExitWarn('Exiting due to user requested termination'));
            });
    }

    /**s
     * @param bool   $state
     * @param string $stateTrue
     * @param string $stateFalse
     *
     * @return string
     */
    private function markupStateToggle(bool $state, string $stateTrue = 'Enabled', string $stateFalse = 'Disabled'): string
    {
        return $state ? sprintf('<fg=green>%s</>', $stateTrue) :
            sprintf('<fg=red>%s</>', $stateFalse);
    }

    /**
     * @param array  $array
     * @param string $separator
     *
     * @return string
     */
    private function arrayToString(array $array, string $separator = ':'): string
    {
        return implode($separator, $array);
    }

    /**
     * @return string[]
     */
    private function getSearchExtensions(): array
    {
        return array_merge(
            $this->io->getInput()->getOption('extension-add'),
            $this->containersManager->getVideos()->getExtensions()
        );
    }

    /**
     * @return string[]
     */
    private function getRmFirstExtensions(): array
    {
        return array_merge(
            $this->io->getInput()->getOption('extension-rm-first'),
            $this->extensionsRemoveFirst
        );
    }

    /**
     * @return string[]
     */
    private function getRmAfterExtensions(): array
    {
        return array_merge(
            $this->io->getInput()->getOption('extension-rm-after'),
            $this->extensionsRemoveAfter
        );
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

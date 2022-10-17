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

use SR\Console\Output\Style\Style;
use SR\Console\Output\Style\StyleAwareTrait;
use SR\Serferals\Component\Console\Options\Runtime\FileOrganizerOptionsRuntime;
use SR\Serferals\Component\Formats\Manager\MediaManager;
use SR\Serferals\Component\Tasks\Filesystem\DirectoryRemoverTask;
use SR\Serferals\Component\Tasks\Filesystem\ExtensionRemoverTask;
use SR\Serferals\Component\Tasks\Filesystem\FileAtomicMoverTask;
use SR\Serferals\Component\Tasks\Filesystem\FileInstructionTask;
use SR\Serferals\Component\Tasks\Filesystem\FinderGeneratorTask;
use SR\Serferals\Component\Tasks\Metadata\FileMetadataTask;
use SR\Serferals\Component\Tasks\Metadata\FileSubtitleAssociateTask;
use SR\Serferals\Component\Tasks\Metadata\TmdbMetadataTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class FileOrganizerCommand extends AbstractCommand
{
    use StyleAwareTrait;

    /**
     * @var MediaManager
     */
    private $formatsManager;

    /**
     * @var string
     */
    private $defaultOutputPath;

    /**
     * @var string[]
     */
    private $defaultMediaExtensions;

    /**
     * @var string[]
     */
    private $defaultSubtitleExtensions;

    /**
     * @var string[]
     */
    private $defaultCleanFirstExtensions;

    /**
     * @var string[]
     */
    private $defaultCleanAfterExtensions;

    /**
     * @var FinderGeneratorTask
     */
    private $finderGenerator;

    /**
     * @var FileMetadataTask
     */
    private $fileMetadata;

    /**
     * @var FileSubtitleAssociateTask
     */
    private $fileSubtitleAssociate;

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
     * @param MediaManager $formatsManager
     * @param string       $defaultOutputPath
     * @param array        $defaultMediaExtensions
     * @param array        $defaultSubtitleExtensions
     * @param array        $defaultCleanFirstExtensions
     * @param array        $defaultCleanAfterExtensions
     */
    public function __construct(MediaManager $formatsManager, string $defaultOutputPath, array $defaultMediaExtensions, array $defaultSubtitleExtensions, array $defaultCleanFirstExtensions, array $defaultCleanAfterExtensions)
    {
        $this->formatsManager = $formatsManager;
        $this->defaultOutputPath = $defaultOutputPath;
        $this->defaultMediaExtensions = $defaultMediaExtensions;
        $this->defaultSubtitleExtensions = $defaultSubtitleExtensions;
        $this->defaultCleanFirstExtensions = $defaultCleanFirstExtensions;
        $this->defaultCleanAfterExtensions = $defaultCleanAfterExtensions;

        parent::__construct();
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
     * @param FileSubtitleAssociateTask $fileSubtitleAssociate
     */
    public function setFileSubtitleAssociate(FileSubtitleAssociateTask $fileSubtitleAssociate)
    {
        $this->fileSubtitleAssociate = $fileSubtitleAssociate;
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

    protected function configure()
    {
        $this
            ->setName('scan')
            ->setDescription('Scan input directories for media files so their metadata can be retrieved and they can be moved into an organized folder structure.')
            ->setHelp('Scan input directory for media files, resolve episode/movie metadata, rename and output using proper directory structure and file names.')
            ->addArgument('search-paths', InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Input paths to search for media files')
            ->addOption('output-path', ['o'], InputOption::VALUE_REQUIRED,
                'Base destination (output) path to write to', $this->defaultOutputPath)
            ->addOption('ext-media', ['e'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Custom media file types to search for (overwriting defaults)', $this->formatsManager->getVideoExtensions())
            ->addOption('ext-media-append', ['E'], InputOption::VALUE_NONE,
                'Append custom media file types instead of overwriting defaults')
            ->addOption('ext-sub', ['t'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Custom subtitle file types to search for (overwriting defaults)', $this->formatsManager->getSubtitleExtensions())
            ->addOption('ext-sub-append', ['T'], InputOption::VALUE_NONE,
                'Append custom subtitle file types instead of overwriting defaults')
            ->addOption('ext-rm-first', ['f'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Custom pre-clean media file types to search for (overwriting defaults)', $this->defaultCleanFirstExtensions)
            ->addOption('ext-rm-first-append', ['F'], InputOption::VALUE_NONE,
                'Append custom pre-clean media file types instead of overwriting defaults')
            ->addOption('ext-rm-after', ['a'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Custom post-clean media file types to search for (overwriting defaults)', $this->defaultCleanAfterExtensions)
            ->addOption('ext-rm-after-append', ['A'], InputOption::VALUE_NONE,
                'Append custom post-clean media file types instead of overwriting defaults')
            ->addOption('no-subs', ['N'], InputOption::VALUE_NONE,
                'Disable automatic subtitle file association with found media files')
            ->addOption('force-episode', [], InputOption::VALUE_NONE,
                'Ignore media files not categorized as <em>episode</em> media types')
            ->addOption('force-movie', [], InputOption::VALUE_NONE,
                'Ignore media files not categorized as <em>movie</em> media types')
            ->addOption('overwrite-blind', ['b'], InputOption::VALUE_NONE,
                'Overwrite existing files with new files <em>blindly</em> regardless of existing ones')
            ->addOption('overwrite-smart', ['s'], InputOption::VALUE_NONE,
                'Overwrite existing files when new files <em>larger</em> than existing ones')
            ->addOption('skip-failures', ['S'], InputOption::VALUE_NONE,
                'Skip over all media files that fail automatic metadata lookup')
            ->addOption('use-copy', [], InputOption::VALUE_NONE,
                'Use the <em>copy</em> command to place media into output path <fg=yellow>[default: "disabled"]</>')
            ->addOption('use-move', [], InputOption::VALUE_NONE,
                'Use the <em>move</em> command to place media into output path <fg=yellow>[default: "enabled"]</>');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runtime = $this->initializeInstanceAndParseOptions($input, $output);

        $this->doFirstTasks($runtime);
        $this->runCoreTasks($runtime);
        $this->doAfterTasks($runtime);

        return $this->writeCommandCompletionSuccess();
    }

    /**
     * @param FileOrganizerOptionsRuntime $runtime
     */
    private function doFirstTasks(FileOrganizerOptionsRuntime $runtime)
    {
        $this->extensionRemover->run($runtime->getSearchPaths(), $runtime->getCleanFirstExt());
    }

    /**
     * @param FileOrganizerOptionsRuntime $runtime
     */
    private function doAfterTasks(FileOrganizerOptionsRuntime $runtime)
    {
        $this->extensionRemover->run($runtime->getSearchPaths(), $runtime->getCleanAfterExt());
        $this->directoryRemover->run($runtime->getSearchPaths());
    }

    /**
     * @param FileOrganizerOptionsRuntime $runtime
     */
    private function runCoreTasks(FileOrganizerOptionsRuntime $runtime)
    {
        $files = $this->fileMetadata
            ->setFinder($this->finderGenerator->paths(...$runtime->getSearchPaths())->extensions(...$runtime->getSearchMediaExt())->find())
            ->setForcedEpisode($runtime->isActionModeEpisodes())
            ->setForcedMovie($runtime->isActionModeMovies())
            ->execute();

        $files = $this->fileSubtitleAssociate
            ->setDisabled($runtime->isSubtitleAssociationsDisabled())
            ->setFinderGenerator($this->finderGenerator)
            ->setExtensions(...$runtime->getSearchSubExt())
            ->execute(...$files);

        $files = $this->tmdbMetadata
            ->setSkipFailures($runtime->isFailureSkipped())
            ->resolve($files);

        $instructions = $this->fileInstruction
            ->setOutputPath($runtime->getOutputPath())
            ->execute($files);

        $this->fileAtomicMover
            ->setMode($runtime->getPlacedModeType())
            ->setBlindOverwrite($runtime->isOverwriteBlind())
            ->setSmartOverwrite($runtime->isOverwriteSmart())
            ->setFileMoveInstructions(...$instructions)
            ->execute();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return FileOrganizerOptionsRuntime
     */
    private function initializeInstanceAndParseOptions(InputInterface $input, OutputInterface $output): FileOrganizerOptionsRuntime
    {
        $this->setStyle(new Style($input, $output));

        $runtime = new FileOrganizerOptionsRuntime(
            $this->io,
            $input->getArgument('search-paths'),
            $input->getOption('output-path'),
            $input->getOption('ext-media-append'),
            $input->getOption('ext-media'),
            $this->formatsManager->getVideoExtensions(),
            $input->getOption('ext-sub-append'),
            $input->getOption('ext-sub'),
            $this->formatsManager->getSubtitleExtensions(),
            $input->getOption('ext-rm-first-append'),
            $input->getOption('ext-rm-first'),
            $this->defaultCleanFirstExtensions,
            $input->getOption('ext-rm-after-append'),
            $input->getOption('ext-rm-after'),
            $this->defaultCleanAfterExtensions,
            $input->getOption('force-movie'),
            $input->getOption('force-episode'),
            $input->getOption('overwrite-blind'),
            $input->getOption('overwrite-smart'),
            $input->getOption('skip-failures'),
            $input->getOption('use-move'),
            $input->getOption('use-copy'),
            $input->getOption('no-subs')
        );

        $this->io->applicationTitle($this->getApplication());
        $this->optionsDescriptor->describe($runtime);

        return $runtime;
    }
}

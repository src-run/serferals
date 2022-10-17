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
use SR\Serferals\Component\Console\Options\Runtime\FileDeduplicatorOptionsRuntime;
use SR\Serferals\Component\Tasks\Filesystem\FinderGeneratorTask;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class FileDeduplicatorCommand extends AbstractCommand
{
    use StyleAwareTrait;

    /**
     * @var FinderGeneratorTask
     */
    private $finderGenerator;

    /**
     * @param FinderGeneratorTask $finderGenerator
     */
    public function setFinderGenerator(FinderGeneratorTask $finderGenerator)
    {
        $this->finderGenerator = $finderGenerator;
    }

    /**
     * configure command name, desc, usage, help, options, etc.
     */
    protected function configure()
    {
        $this
            ->setName('dupe')
            ->setDescription('Scan the input director(ies) and remove lower-rated duplicates of media')
            ->setHelp('Scan the input director(ies) for media files and find duplicates of the same source and delete the lower-rated copies')
            ->addOption('not-file', ['f'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Files matching name pattern(s) will be ignored by the deduplicator algorithm')
            ->addOption('not-path', ['p'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Files matching path pattern(s) will be ignored by the deduplicator algorithm')
            ->addOption('not-ext', ['e'], InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Files matching extension(s) will be ignored by the deduplicator algorithm')
            ->addOption('max-size', ['s'], InputOption::VALUE_REQUIRED,
                'Maximum size of files to apply the deduplicator algorithm')
            ->addOption('dry-run', ['d'], InputOption::VALUE_NONE,
                'Disables actual deletion of results and enables report-only mode')
            ->addArgument('search-paths', InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'One or more paths to search for duplicate media files in');
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

        $this->runDedupTask($runtime);

        return $this->writeCommandCompletionSuccess();
    }

    private function runDedupTask(FileDeduplicatorOptionsRuntime $runtime): void
    {
        $files = $this
            ->finderGenerator
            ->paths(...$runtime->getSearchPaths())
            ->notPaths(...$runtime->getIgnoredPaths())
            ->notNames(...$runtime->getIgnoredFiles())
            ->notExts(...$runtime->getIgnoredExtensions());

        var_dump(iterator_to_array($files->find()));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return FileDeduplicatorOptionsRuntime
     */
    private function initializeInstanceAndParseOptions(InputInterface $input, OutputInterface $output): FileDeduplicatorOptionsRuntime
    {
        $this->setStyle(new Style($input, $output));
        $this->io->applicationTitle($this->getApplication());

        $runtime = new FileDeduplicatorOptionsRuntime(
            $this->io,
            $input->getArgument('search-paths'),
            $input->getOption('not-ext'),
            $input->getOption('not-file'),
            $input->getOption('not-path'),
            $input->getOption('dry-run'),
            $input->getOption('max-size')
        );

        $this->optionsDescriptor->describe($runtime);

        return $runtime;
    }
}

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
use SR\Serferals\Component\Fixture\FixtureData;
use SR\Serferals\Component\Fixture\FixtureEpisodeData;
use SR\Serferals\Component\Fixture\FixtureMovieData;
use SR\Serferals\Component\Operation\FileResolverOperation;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class DuplicatesCommand
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
                new InputOption('ext', ['x'], InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'Input extensions to consider media.', ['mov', 'mkv', 'mp4', 'avi']),
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

        $this->checkRequirements();

        $this->io()->applicationTitle(
            $this->getApplication()->getName(),
            $this->getApplication()->getVersion(),
            ['by', 'Rob Frawley 2nd <rmf@src.run>']);

        $this->io()->comment(sprintf('Running command <info>%s</info>', 'dups'));

        $inputExtensions = $input->getOption('ext');
        list($inputPaths, $inputInvalidPaths) = $this->validatePaths(true, ...$input->getArgument('input-dirs'));

        if (count($inputInvalidPaths) !== 0) {
            $this->io()->error('Invalid input path(s): '.implode(', ', $inputInvalidPaths));
            return 255;
        }

        $this->showRuntimeConfiguration($inputPaths, $inputExtensions);
        $this->io()->comment('Searching input paths', false);

        $finder = Finder::create();

        foreach ($inputPaths as $p) {
            $finder
                ->ignoreUnreadableDirs()
                ->in($p);
        }

        foreach ($inputExtensions as $extension) {
            $finder->name('*.'.$extension);
        }

        $finderFiles = $finder->files();

        $this->io()->comment(sprintf('Found <info>%s</info> media files', count($finderFiles)));

        $fileSet = [];
        foreach ($finderFiles as $f) {
            $fileSet[] = $f;
        }

        $fileResolver = $this->operationFileResolver();
        $fixtureSet = [];
        foreach ($finderFiles as $f) {
            $fixtureSet[] = $fileResolver->parseFile($f);
        }

        $skipSet = $resolveSet = [];
        foreach ($fileSet as $i => $f) {
            $this->match($f, $i, $skipSet, $resolveSet, $fileSet, $fixtureSet);
        }

        $this->resolveSet($resolveSet);

        $this->io()->success('Done');

        return 0;
    }

    /**
     * @param array $resolveSet
     */
    private function resolveSet(array $resolveSet)
    {
        foreach ($resolveSet as $i => $r) {
            $this->resolve($i, $r);
        }
    }

    /**
     * @param int   $i
     * @param array $r
     */
    private function resolve($i, array $r)
    {
        dump($i);
        dump($r);
    }

    /**
     * @param SplFileInfo                             $file
     * @param int                                     $i
     * @param int[]                                   $skipSet
     * @param array                                   $resolveSet
     * @param SplFileInfo[]                           $fileSet
     * @param FixtureEpisodeData[]|FixtureMovieData[] $fixtureSet
     */
    private function match(SplFileInfo $file, $i, array &$skipSet, array &$resolveSet, array &$fileSet, array &$fixtureSet)
    {
        $matches = null;
        $fixture = $fixtureSet[$i];

        if (in_array($i, $skipSet)) {
            die('Found '.$i);
        }

        if ($fixture instanceof FixtureEpisodeData) {
            $resolveSet[$i] = $this->matchEpisodes($i, $skipSet, $fixtureSet);
        }

        if ($fixture instanceof FixtureMovieData) {
            $resolveSet[$i] = $this->matchMovies($i, $skipSet, $fixtureSet);
        }

        if ($resolveSet[$i] === null) {
            unset($resolveSet[$i]);
            unset($fileSet[$i]);
            unset($fixtureSet[$i]);
        }
    }

    /**
     * @param int                                     $i
     * @param int[]                                   $skipSet
     * @param FixtureEpisodeData[]|FixtureMovieData[] $fixtureSet
     *
     * @return null|FixtureEpisodeData
     */
    private function matchEpisodes($i, array &$skipSet, array $fixtureSet)
    {
        $current = $fixtureSet[$i];
        $matches = array_filter(
            $fixtureSet,
            function (FixtureData $fixture) use ($current, $i, &$skipSet) {
                if ($fixture === $current || !($fixture instanceof FixtureEpisodeData)) {
                    return false;
                }

                if ((!empty($fixture->getName()) && !empty($fixture->getSeasonNumber()) && !empty($fixture->getEpisodeNumberStart())) &&
                    ($fixture->getName() === $current->getName() && $fixture->getSeasonNumber() === $current->getSeasonNumber() && $fixture->getEpisodeNumberStart() === $current->getEpisodeNumberStart()))
                {
                    if (!in_array($i, $skipSet)) {
                        $skipSet[] = $i;
                    }

                    return true;
                }

                return false;
            }
        );

        foreach ($matches as $index => $value) {
            if (in_array($index, $skipSet)) {
                unset($matches[$index]);
            }
        }

        if (count($matches) === 0) {
            return null;
        }

        $matches[$i] = $current;

        return $matches;
    }

    /**
     * @param int                                     $i
     * @param int[]                                   $skipSet
     * @param FixtureEpisodeData[]|FixtureMovieData[] $fixtureSet
     *
     * @return null|FixtureEpisodeData
     */
    private function matchMovies($i, array &$skipSet, array $fixtureSet)
    {
        $current = $fixtureSet[$i];
        $matches = array_filter(
            $fixtureSet,
            function (FixtureData $fixture) use ($current, $i, &$skipSet) {
                if ($fixture === $current || !($fixture instanceof FixtureMovieData)) {
                    return false;
                }

                if ((!empty($fixture->getName()) && !empty($fixture->getYear()) && !empty($fixture->getId())) &&
                    ($fixture->getName() === $current->getName() && $fixture->getYear() === $current->getYear() && $fixture->getId() === $current->getId()))
                {
                    if (!in_array($i, $skipSet)) {
                        $skipSet[] = $i;
                    }

                    return true;
                }

                return false;
            }
        );

        foreach ($matches as $index => $value) {
            if (in_array($index, $skipSet)) {
                unset($matches[$index]);
            }
        }

        if (count($matches) === 0) {
            return null;
        }

        $matches[$i] = $current;

        return $matches;
    }

    /**
     * @return FileResolverOperation
     */
    private function operationFileResolver()
    {
        return $this->getService('sr.serferals.operation_file_resolver');
    }

    /**
     * @param string[] $inputPaths
     * @param string[] $inputExtensions
     */
    private function showRuntimeConfiguration(array $inputPaths, array $inputExtensions)
    {
        $tableRows = [];

        foreach ($inputPaths as $i => $path) {
            $tableRows[] = [sprintf('Input Path %d', ($i+1)), $path];
        }

        foreach ($inputExtensions as $i => $extension) {
            $tableRows[] = [sprintf('Extension %d', ($i+1)), $extension];
        }

        $this->ioVerbose(function (StyleInterface $io) use ($tableRows) {
            $io->comment('Listing runtime configuration');
            $io->table($tableRows);
        });

        $this->ioVeryVerbose(function () {
            if (false === $this->io()->confirm('Continue using these values?', true)) {
                $this->endError();
            }
        });

        $this->ioNotVerbose(function (StyleInterface $io) use ($inputExtensions, $inputPaths) {
            $io->comment(
                sprintf(
                    'Filter inputs against <info>*.(%s)</info>',
                    implode('|', $inputExtensions)
                ),
                false
            );

            $io->comment(
                sprintf(
                    'Using input set <info>%s</info>',
                    implode('|', $inputPaths)
                ),
                false
            );
        });
    }
}

/* EOF */

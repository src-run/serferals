<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Operation;

use SR\Console\Style\StyleAwareTrait;
use SR\Serferals\Component\Fixture\FixtureData;
use SR\Serferals\Component\Fixture\FixtureEpisodeData;
use SR\Serferals\Component\Fixture\FixtureMovieData;
use SR\Spl\File\SplFileInfo as FileInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class FileResolverOperation.
 */
class FileResolverOperation
{
    use StyleAwareTrait;

    /**
     * @var string
     */
    const TYPE_MOVIE = 'movie';

    /**
     * @var string
     */
    const TYPE_EPISODE = 'episode';

    /**
     * @var Finder
     */
    protected $finder;

    /**
     * @var bool
     */
    protected $forcedMovie = false;

    /**
     * @var bool
     */
    protected $forcedEpisode = false;

    /**
     * @return bool
     */
    public function isForcedMovie()
    {
        return $this->forcedMovie;
    }

    /**
     * @param bool $modeMovie
     *
     * @return FileResolverOperation
     */
    public function setForcedMovie($modeMovie)
    {
        $this->forcedMovie = $modeMovie;

        return $this;
    }

    /**
     * @return bool
     */
    public function isForcedEpisode()
    {
        return $this->forcedEpisode;
    }

    /**
     * @param bool $forcedEpisode
     *
     * @return FileResolverOperation
     */
    public function setForcedEpisode($forcedEpisode)
    {
        $this->forcedEpisode = $forcedEpisode;

        return $this;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @param Finder $finder
     *
     * @return $this
     */
    public function setFinder(Finder $finder)
    {
        $this->finder = $finder;

        return $this;
    }

    /**
     * @return FixtureData[]
     */
    public function getItems()
    {
        $fixtureCollection = [];

        foreach ($this->finder as $file) {
            $fixtureCollection[] = $this->parseFile($file);
        }

        if ($this->isForcedEpisode()) {
            return array_filter($fixtureCollection, function ($item) {
                return $item instanceof FixtureEpisodeData;
            });
        }

        if ($this->isForcedMovie()) {
            return array_filter($fixtureCollection, function ($item) {
                return $item instanceof FixtureMovieData;
            });
        }

        return $fixtureCollection;
    }

    /**
     * @param SplFileInfo $file
     *
     * @return FixtureEpisodeData|FixtureMovieData
     */
    public function parseFile(SplFileInfo $file)
    {
        $file = new FileInfo($file);

        $episode = $this->parseFileAsEpisode($file);

        if ($episode->hasEpisodeNumberStart() && $episode->hasSeasonNumber()) {
            return $episode;
        }

        $movie = $this->parseFileAsMovie($file);

        return $movie;
    }

    /**
     * @param FileInfo $file
     *
     * @return FixtureEpisodeData
     */
    public function parseFileAsEpisode(FileInfo $file)
    {
        $fixture = FixtureEpisodeData::create($file);
        $baseName = $fixture->getFile()->getBasename();

        $this->cleanEpisodeFileName($baseName);
        $this->parseEpisodeYear($fixture, $baseName);
        $this->parseEpisodeNumbers($fixture, $baseName);
        $this->parseEpisodeName($fixture, $baseName);
        $this->parseEpisodeTitle($fixture, $baseName);

        return $fixture;
    }

    /**
     * @param FileInfo $file
     *
     * @return FixtureMovieData
     */
    public function parseFileAsMovie(FileInfo $file)
    {
        $fixture = FixtureMovieData::create($file);
        $baseName = $fixture->getFile()->getBasename();

        $this->cleanMovieFileName($baseName);
        $this->parseMovieYear($fixture, $baseName);
        $this->parseMovieTitle($fixture, $baseName);

        return $fixture;
    }

    /**
     * @param string $baseName
     */
    protected function cleanEpisodeFileName(&$baseName)
    {
        $this->cleanFileName($baseName);
    }

    /**
     * @param FixtureEpisodeData $fixture
     * @param string             $baseName
     */
    protected function parseEpisodeName(FixtureEpisodeData $fixture, &$baseName)
    {
        $name = $baseName[0];

        $regex = [
            '{\b(\[?\(?us|uk\)?\]?)\b}i' => '',
            '{([a-z])([A-Z][a-z])}' => '$1 $2',
            '{\s+}' => ' ',
        ];
        foreach ($regex as $search => $replace) {
            $name = ucwords(trim(preg_replace($search, $replace, $name)));
        }

        $search = [
            '.',
            '-',
            '[',
        ];
        foreach ($search as $s) {
            $name = ucwords(trim(str_replace($s, ' ', $name)));
        }

        $fixture->setName($name);
    }

    /**
     * @param FixtureEpisodeData $fixture
     * @param string             $baseName
     */
    protected function parseEpisodeYear(FixtureEpisodeData $fixture, &$baseName)
    {
        if (1 !== preg_match('{\b(20[0-9][0-9])\b}', $baseName, $match)) {
            return;
        }

        $fixture->setYear($match[1]);

        $baseName = str_replace($match[0], '', $baseName);
    }

    /**
     * @param FixtureEpisodeData $fixture
     * @param string             $baseName
     */
    protected function parseEpisodeNumbers(FixtureEpisodeData $fixture, &$baseName)
    {
        if (1 !== preg_match('{s([0-9]+)e([0-9]+)-?([0-9]+)?}i', $baseName, $match) &&
            1 !== preg_match('{([0-9]{1,2})x([0-9]{1,2})}i', $baseName, $match) &&
            1 !== preg_match('{([0-9]{1,2})([0-9]{2})}', $baseName, $match)) {
            return;
        }

        $fixture->setSeasonNumber(isset($match[1]) ? $match[1] : 0);
        $fixture->setEpisodeNumberStart(isset($match[2]) ? $match[2] : 0);

        if (isset($match[3])) {
            $fixture->setEpisodeNumberEnd($match[3]);
        }

        $baseName = [
            substr($baseName, 0, strpos($baseName, $match[0])),
            substr($baseName, strpos($baseName, $match[0]) + strlen($match[0])),
        ];
    }

    /**
     * @param FixtureEpisodeData $fixture
     * @param string             $baseName
     */
    protected function parseEpisodeTitle(FixtureEpisodeData $fixture, &$baseName)
    {
    }

    /**
     * @param FixtureMovieData $fixture
     * @param string           $baseName
     */
    protected function parseMovieYear(FixtureMovieData $fixture, &$baseName)
    {
        if (1 !== preg_match('{\b([0-9]{4})\b}', $baseName, $match)) {
            return;
        }

        $fixture->setYear($match[1]);

        $baseName = [
            substr($baseName, 0, strpos($baseName, $match[0])),
            substr($baseName, strpos($baseName, $match[0]) + strlen($match[0])),
        ];
    }

    /**
     * @param FixtureMovieData $fixture
     * @param string           $baseName
     */
    protected function parseMovieTitle(FixtureMovieData $fixture, &$baseName)
    {
        $name = $baseName[0];

        foreach (['.', '-', '['] as $search) {
            $name = ucwords(trim(str_replace($search, ' ', $name)));
        }

        $fixture->setName($name);
    }

    /**
     * @param string $baseName
     */
    protected function cleanMovieFileName(&$baseName)
    {
        $this->cleanFileName($baseName);
    }

    /**
     * @param string $baseName
     */
    protected function cleanFileName(&$baseName)
    {
        $replacements = [
            '{\b720p?\b}' => '',
            '{\b1080p?\b}' => '',
            '{\bHDRip\b}' => '',
            '{\bx?h?264\b}' => '',
            '{\bACC\b}' => '',
            '{\bHDTV\b}' => '',
            '{tt[0-9]+}' => '',
            '{\(}' => '',
            '{\)}' => '',
            '{\[}' => '',
            '{\]}' => '',
            '{\s+}' => ' ',
        ];

        foreach ($replacements as $search => $replace) {
            $baseName = preg_replace($search, $replace, $baseName);
        }
    }
}

/* EOF */

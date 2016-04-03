<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace RMF\Serferals\Component\Operation;

use RMF\Serferals\Component\Console\InputOutputAwareTrait;
use RMF\Serferals\Component\Fixture\FixtureData;
use RMF\Serferals\Component\Fixture\FixtureEpisodeData;
use RMF\Serferals\Component\Queue\QueueEpisodeItem;
use SR\Utility\StringUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class ParseFileNamesOperation
 */
class ParseFileNamesOperation
{
    use InputOutputAwareTrait;

    /**
     * @var Finder
     */
    protected $finder;

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
    public function using(Finder $finder)
    {
        $this->finder = $finder;

        return $this;
    }

    public function getItems()
    {
        $fixtureCollection = [];

        foreach ($this->finder as $file) {
            $fixtureCollection[] = $this->parseFile(FixtureEpisodeData::create($file));
        }

        return $fixtureCollection;
    }

    protected function parseFileYear(FixtureData $fixture, &$baseName)
    {
        if (1 !== preg_match('{(20[0-9][0-9])}', $baseName, $match)) {
            return;
        }

        $fixture->setYear($match[1]);

        $baseName = str_replace($match[0], '', $baseName);
    }

    protected function parseFileName(FixtureData $fixture, &$baseName)
    {
        $name = $baseName[0];
        $name = preg_replace('{(us|uk)}i', '', $name);
        $name = ucwords(trim(str_replace('.', ' ', $name)));

        $fixture->setName($name);
    }

    protected function parseFileSeasonEpisodeNumber(FixtureEpisodeData $fixture, &$baseName)
    {
        if (1 !== preg_match('{s([0-9]+)e([0-9]+)-?([0-9]+)?}i', $baseName, $match) &&
            1 !== preg_match('{([0-9]{1,2})x([0-9]{1,2})}i', $baseName, $match) &&
            1 !== preg_match('{([0-9]{1,2})([0-9]{2})}', $baseName, $match))
        {
            return;
        }

        $fixture->setSeasonNumber(isset($match[1]) ? $match[1] : 0);
        $fixture->setEpisodeNumberStart(isset($match[2]) ? $match[2] : 0);

        if (isset($match[3])) {
            $fixture->setEpisodeNumberEnd($match[3]);
        }

        $baseName = [
            substr($baseName, 0, strpos($baseName, $match[0])),
            substr($baseName, strpos($baseName, $match[0])+strlen($match[0]))
        ];
    }

    protected function parseFileTitle(FixtureEpisodeData $fixture, &$baseName)
    {
    }

    protected function parseFile(FixtureEpisodeData $fixture)
    {
        $baseName = $fixture->getFile()->getBasename();

        $this->parseFileYear($fixture, $baseName);
        $this->parseFileSeasonEpisodeNumber($fixture, $baseName);
        $this->parseFileName($fixture, $baseName);
        $this->parseFileTitle($fixture, $baseName);

        $fixture->setFileSize(filesize($fixture->getFile()->getRealPath()));

        return $fixture;
    }
}
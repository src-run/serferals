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
use SR\Utility\StringUtil;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tmdb\ApiToken;
use Tmdb\Client;
use Tmdb\Model\Collection\ResultCollection;
use Tmdb\Model\Search\SearchQuery;
use Tmdb\Model\Tv;
use Tmdb\Repository\SearchRepository;
use Tmdb\Repository\TvEpisodeRepository;

/**
 * Class LookupResolverOperation
 */
class LookupResolverOperation
{
    use InputOutputAwareTrait;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $apiLogPath;

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
     * @param string $apiKey
     * @param string $apiLogPath
     */
    public function setApiOptions($apiKey, $apiLogPath)
    {
        $this->apiKey = $apiKey;
        $this->apiLogPath = $apiLogPath;
    }

    public function resolve(array $fixtures)
    {
        $this->io()->section('Resolving File Metadata');

        foreach ($fixtures as $i => $f) {
            $this->resolveSingle($f, $i, count($fixtures));
        }

        return array_filter($fixtures, function (FixtureData $fixture) {
            return $fixture->isEnabled();
        });
    }

    public function resolveSingle(FixtureEpisodeData $fixture, $i, $count)
    {
        while (true) {
            $this->io()->block($fixture->getFile()->getRelativePathname(), sprintf('FILE %d of %d', $i+1, $count), 'fg=white;bg=cyan', ' ', true);
            $client = $this->getClient();
            $repo = new SearchRepository($client);
            $query = new SearchQuery\TvSearchQuery();
            $results = $repo->searchTv($fixture->getName(), $query);
            $episode = $this->resolveEpisode($fixture, $this->getFirstResult($results));

            if ($results->count() === 0 || $episode === null) {
                $this->io()->caution('Could not resolve metadata with information parsed from filename!');
            } else {
                $this->outFirstMatch($fixture, $this->getFirstResult($results), $episode);
            }

            $this->io()->text('<comment>Actions available in this context: </comment> <em>C</em> (continue); <em>E</em> (edit); <em>S</em> (skip); <em>R</em> (remove)');
            $action = strtolower($this->io()->ask('Enter desired action for item '.($i+1), $results->count() === 0 || $episode === null ? 'skip' : 'continue'));

            switch ($action) {
                case 'c':
                case 'continue':
                    $this->hydrateFixture($fixture, $this->getFirstResult($results), $episode);
                    break 2;

                case 'e':
                case 'edit':
                    $this->io()->error('Editing not yet implemented!');
                    break;

                case 's':
                case 'skip':
                    break 2;

                case 'r':
                case 'rm':
                    $unlinkRet = $this->unlink($fixture);
                    if ($unlinkRet === 1) {
                        break 1;
                    }
                    break 2;
            }
        }
    }

    private function unlink(FixtureEpisodeData $fixture)
    {
        $this->io()->caution('Removing '.$fixture->getFile()->getRelativePathname().' from disk!');

        $response = $this->io()->confirm('Continue with deletion?', true);

        if ($response === false) {
            $this->io()->text('Canceling removal operation.');
            return 1;
        }

        $file = $fixture->getFile()->getRealPath();
        if (!is_writable($file)) {
            $this->io()->warning('Inefficient permissions to execute removal operation.');
            return 1;
        }

        if (false === unlink($file)) {
            $this->io()->warning('Unknown error during removal operation.');
            return 1;
        }

        $fixture->setEnabled(false);
        $this->io()->success('Removal operation complete.');

        return 2;
    }

    private function hydrateFixture(FixtureEpisodeData $fixture, Tv $show = null, Tv\Episode $episode = null)
    {
        if ($show === null || $episode === null) {
            return;
        }

        $fixture->setName($show->getName());
        $fixture->setTitle($episode->getName());
        $fixture->setEpisodeNumberStart($episode->getEpisodeNumber());
        $fixture->setSeasonNumber($episode->getSeasonNumber());
        $fixture->setId($episode->getId());
        $fixture->setYear($show->getFirstAirDate()->format('Y'));
        $fixture->setEnabled(true);
    }

    /**
     * @param ResultCollection $result
     *
     * @return Tv
     */
    private function getFirstResult(ResultCollection $result)
    {
        $keys = $result->getKeys();

        return $result->get(@$keys[0]);
    }

    private function resolveEpisode(FixtureEpisodeData $fixture, Tv $result = null)
    {
        if ($result === null) {
            return null;
        }

        try {
            $client = $this->getClient();
            $repo = new TvEpisodeRepository($client);
            $episode = $repo->load($result->getId(), $fixture->getSeasonNumber(), $fixture->getEpisodeNumberStart());

            if ($episode instanceof Tv\Episode) {
                return $episode;
            }
        } catch (\Exception $e) {}

        return null;
    }

    private function outFirstMatch(FixtureEpisodeData $fixture, Tv $show, Tv\Episode $episode)
    {
        $this->io()->success('Found match: '.$show->getName().' - S'.str_pad($episode->getSeasonNumber(), 2, 0, STR_PAD_LEFT).'E'.str_pad($episode->getEpisodeNumber(), 2, 0, STR_PAD_LEFT).' - '.$episode->getName().' [show='.$show->getId().'&episode='.$episode->getId().']');

        $rows = [
            ['Path Name', $fixture->getFile()->getRelativePathname()],
            ['Show Name', $show->getName()],
            ['Season/Ep', $episode->getSeasonNumber().'/'.$episode->getEpisodeNumber()],
            ['Title', $episode->getName()],
            ['Date Aired', $episode->getAirDate()->format('Y\-m\-d')],
        ];

        if ($this->io()->isVerbose() && false) {
            $overview = $episode->getOverview();
            $i = 0;

            $rows[] = new TableSeparator();

            while ($i < strlen($overview)) {
                $rows[] = [
                    $i === 0 ? 'Overview' : '',
                    substr($overview, $i, 80),
                ];
                $i = $i + 80;
            }
        }

        if ($this->io()->isVerbose()) {
            $this->io()->table([], $rows);
        }
    }

    public function getClient()
    {
        static $client = null;

        if ($client === null) {
            $token = new ApiToken($this->apiKey);
            $client = new Client($token, [
                'log' => [
                    'enabled' => true,
                    'path'    => $this->apiLogPath
                ]
            ]);
        }

        return $client;
    }
}
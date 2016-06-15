<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Tmdb;

use SR\Serferals\Component\Fixture\FixtureData;
use SR\Serferals\Component\Fixture\FixtureEpisodeData;
use Tmdb\Model\Search\SearchQuery\TvSearchQuery;
use Tmdb\Model\Tv;
use Tmdb\Repository\TvEpisodeRepository;

/**
 * Class EpisodeResolver.
 */
class EpisodeResolver extends AbstractResolver
{
    /**
     * @var string
     */
    const TYPE = 'episode';

    /**
     * @return TvSearchQuery
     */
    protected function getQuery()
    {
        return new TvSearchQuery();
    }

    /**
     * @return TvEpisodeRepository
     */
    protected function getSingleRepository()
    {
        return new TvEpisodeRepository($this->getClient(false));
    }

    /**
     * @param FixtureData $fixture
     * @param string      $method
     *
     * @return $this
     */
    public function resolve(FixtureData $fixture, $method = 'searchTv')
    {
        return parent::resolve($fixture, $method);
    }

    /**
     * @param FixtureEpisodeData $fixture
     * @param Tv|null            $result
     *
     * @return null|\Tmdb\Model\AbstractModel|Tv\Episode
     */
    public function resolveSingle(FixtureEpisodeData $fixture, Tv $result = null)
    {
        if ($result === null) {
            return null;
        }

        $item = null;

        try {
            $repo = $this->getSingleRepository();
            $item = $repo->load($result->getId(), $fixture->getSeasonNumber(), $fixture->getEpisodeNumberStart());
        } catch (\Exception $e) {
        }

        return $item instanceof Tv\Episode ? $item : null;
    }
}

/* EOF */

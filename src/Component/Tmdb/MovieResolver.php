<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace RMF\Serferals\Component\Tmdb;

use RMF\Serferals\Component\Fixture\FixtureData;
use Tmdb\Model\Movie;
use Tmdb\Model\Search\SearchQuery\MovieSearchQuery;
use Tmdb\Repository\MovieRepository;

/**
 * Class MovieResolver
 */
class MovieResolver extends AbstractResolver
{
    /**
     * @var string
     */
    const TYPE = 'movie';

    /**
     * @return MovieSearchQuery
     */
    protected function getQuery()
    {
        return new MovieSearchQuery();
    }

    /**
     * @return MovieRepository
     */
    protected function getSingleRepository()
    {
        return new MovieRepository($this->getClient(false));
    }

    /**
     * @param FixtureData $fixture
     * @param string      $method
     *
     * @return $this
     */
    public function resolve(FixtureData $fixture, $method = 'searchMovie')
    {
        return parent::resolve($fixture, $method);
    }
}

/* EOF */

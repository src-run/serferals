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

use SR\Console\Output\Style\StyleAwareTrait;
use SR\Serferals\Component\Model\MediaMetadataModel;
use Tmdb\ApiToken;
use Tmdb\Client;
use Tmdb\Model\Collection\ResultCollection;
use Tmdb\Model\Search\SearchQuery\MovieSearchQuery;
use Tmdb\Model\Search\SearchQuery\TvSearchQuery;
use Tmdb\Repository\MovieRepository;
use Tmdb\Repository\SearchRepository;
use Tmdb\Repository\TvEpisodeRepository;

/**
 * Class AbstractResolver.
 */
abstract class AbstractResolver
{
    use StyleAwareTrait;

    /**
     * @var string
     */
    const TYPE = 'abstract';

    /**
     * @var ResultCollection|null
     */
    protected $results;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string|null
     */
    protected $apiLogPath;

    /**
     * @var array
     */
    protected $apiOptions = [];

    /**
     * @var Client|null
     */
    protected $apiClient;

    /**
     * @param string $key
     * @param string $logPath
     */
    public function __construct($key, $logPath = null, $options = [])
    {
        $this->apiKey = $key;
        $this->apiLogPath = $logPath;
        $this->apiOptions = $options;
    }

    /**
     * @return null|ResultCollection
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param bool $new
     *
     * @return Client
     */
    public function getClient($new = true)
    {
        if ($this->apiClient === null || $new === true) {
            $this->apiClient = $this->initClient();
        }

        return $this->apiClient;
    }

    /**
     * @return Client
     */
    protected function initClient()
    {
        $options = $this->apiOptions;

        $options['log'] = [
            'enabled' => $this->apiLogPath !== null,
            'path' => $this->apiLogPath,
        ];

        return new Client(new ApiToken($this->apiKey), $options);
    }

    /**
     * @return SearchRepository
     */
    protected function getSearchRepository()
    {
        return new SearchRepository($this->getClient());
    }

    /**
     * @return TvEpisodeRepository|MovieRepository
     */
    abstract protected function getSingleRepository();

    /**
     * @return TvSearchQuery|MovieSearchQuery
     */
    abstract protected function getQuery();

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @param MediaMetadataModel $fixture
     * @param string      $method
     *
     * @return AbstractResolver
     */
    public function resolve(MediaMetadataModel $fixture, $method)
    {
        $r = $this->getSearchRepository();
        $q = $this->getQuery();

        try {
            $this->results = @call_user_func_array([$r, $method], [$fixture->getName(), $q]);
        } catch (\Exception $e) {
        }

        return $this;
    }
}


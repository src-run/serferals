<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace RMF\Serferals\Component\Fixture;

/**
 * Class FixtureMovieData
 */
class FixtureMovieData extends FixtureData
{
    /**
     * @var null|int
     */
    protected $seasonNumber;

    /**
     * @var null|int
     */
    protected $episodeNumberStart;

    /**
     * @var null|int
     */
    protected $episodeNumberEnd;

    /**
     * @var null|string
     */
    protected $title;

    /**
     * @return int|null
     */
    public function getSeasonNumber()
    {
        return $this->seasonNumber;
    }

    /**
     * @param int|null $seasonNumber
     *
     * @return $this
     */
    public function setSeasonNumber($seasonNumber)
    {
        $this->seasonNumber = (int) $seasonNumber;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSeasonNumber()
    {
        return $this->seasonNumber !== null;
    }

    /**
     * @return int|null
     */
    public function getEpisodeNumberStart()
    {
        return $this->episodeNumberStart;
    }

    /**
     * @param int|null $episodeNumberStart
     *
     * @return $this
     */
    public function setEpisodeNumberStart($episodeNumberStart)
    {
        $this->episodeNumberStart = (int) $episodeNumberStart;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasEpisodeNumberStart()
    {
        return $this->episodeNumberStart !== null;
    }

    /**
     * @return int|null
     */
    public function getEpisodeNumberEnd()
    {
        return $this->episodeNumberEnd;
    }

    /**
     * @param int|null $episodeNumberEnd
     *
     * @return $this
     */
    public function setEpisodeNumberEnd($episodeNumberEnd)
    {
        $this->episodeNumberEnd = (int) $episodeNumberEnd;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasEpisodeNumberEnd()
    {
        return $this->episodeNumberEnd !== null;
    }

    /**
     * @return null|string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param null|string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = (string) $title;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasTitle()
    {
        return $this->title !== null;
    }
}

/* EOF */

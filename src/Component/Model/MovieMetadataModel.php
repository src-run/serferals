<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Model;

use SR\Serferals\Component\Tmdb\MovieResolver;

class MovieMetadataModel extends MediaMetadataModel
{
    /**
     * @var string
     */
    const TYPE = MovieResolver::TYPE;

    /**
     * @var string|null
     */
    protected $imdb;

    /**
     * @return string|null
     */
    public function getImdb()
    {
        return $this->imdb;
    }

    /**
     * @param string|null $imdb
     *
     * @return $this
     */
    public function setImdb($imdb)
    {
        $this->imdb = $imdb;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getFieldsStatic()
    {
        return parent::getFieldsStatic();
    }

    /**
     * @return string[]
     */
    public function getFieldsEditable()
    {
        return parent::getFieldsEditable();
    }
}


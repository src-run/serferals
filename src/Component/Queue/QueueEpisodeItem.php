<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace RMF\Serferals\Component\Queue;

/**
 * Class QueueEpisodeItem
 */
class QueueEpisodeItem
{
    /**
     * @var \SplFileInfo
     */
    public $file;

    /**
     * @var string
     */
    public $name = null;

    /**
     * @var int|null
     */
    public $year = null;

    /**
     * @var int|null
     */
    public $season = null;

    /**
     * @var int|null
     */
    public $start = null;

    /**
     * @var int|null
     */
    public $end = null;

    /**
     * @var string|null
     */
    public $title = null;

    /**
     * @var bool
     */
    public $enabled = false;
}
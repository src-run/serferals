<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Console\Helper;

use SR\Exception\Logic\InvalidArgumentException;
use SR\Serferals\Component\Console\Helper\Action\ActionModel;

class SelectionActionHelper extends ActionHelper
{
    /**
     * @var ActionModel[]
     */
    private $selectionActions;

    public function __construct(array $keys, array $vals)
    {
        $this->setupSelectionActions($keys, $vals);
        parent::__construct();
    }

    /**
     * @return void
     */
    public function setupCollection(): void
    {
    }

    private function setupSelectionActions(array $keys, array $vals)
    {
        if (count($keys) !== count($vals)) {
            throw new InvalidArgumentException('The same number of keys and values must be provided!');
        }

        foreach ($keys as $i => $v) {
            $this->selectionActions[] = new ActionModel($i, $v, $vals[$i], false);
        }
    }
}

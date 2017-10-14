<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Console\Helper\Action;

final class ActionCollection
{
    /**
     * @var ActionModel[]
     */
    private $actions = [];

    /**
     * @param ActionModel[] ...$actions
     */
    public function __construct(ActionModel ...$actions)
    {
        foreach ($actions as $a) {
            $this->add($a);
        }
    }

    /**
     * @param ActionModel $action
     *
     * @return self
     */
    public function add(ActionModel $action): self
    {
        if (!in_array($action, $this->actions)) {
            $action->setCollection($this);
            $this->actions[] = $action;
        }

        return $this;
    }

    /**
     * @return ActionModel[]
     */
    public function get(): array
    {
        return $this->actions;
    }

    /**
     * @return \Generator|ActionModel[]
     */
    public function each(): \Generator
    {
        foreach ($this->actions as $action) {
            yield $action;
        }
    }
}

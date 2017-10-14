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

use SR\Console\Output\Style\StyleAwareTrait;
use SR\Exception\Runtime\RuntimeException;
use SR\Serferals\Component\Console\Helper\Action\ActionCollection;
use SR\Serferals\Component\Console\Helper\Action\ActionModel;

abstract class ActionHelper
{
    use StyleAwareTrait;

    /**
     * @var ActionCollection
     */
    protected $collection;

    /**
     * @var bool
     */
    protected $verbose;

    /**
     * @var ActionModel
     */
    protected $quitAction;

    /**
     * @var ActionModel
     */
    protected $helpAction;

    public function __construct()
    {
        $this->collection = new ActionCollection();
        $this->verbose = false;
        $this->quitAction = new ActionModel('Q', 'Quit', 'Quit without writing anything', true);
        $this->helpAction = new ActionModel('?', 'Help', 'Print the complete, verbose action listing', false);

        $this->setupCollection();

        $this->collection->add($this->quitAction);
        $this->collection->add($this->helpAction);

        $this->sanitizeCollection();
    }

    /**
     * @param bool $verbose
     *
     * @return $this
     */
    public function setVerbose(bool $verbose = true)
    {
        $this->verbose = $verbose;

        return $this;
    }

    /**
     * @return ActionCollection
     */
    public function getCollection(): ActionCollection
    {
        return $this->collection;
    }

    /**
     * @param string|null $defaultActionChar
     *
     * @return ActionModel
     */
    public function writeActionsAndGetResult(string $defaultActionChar = null): ActionModel
    {
        while (true) {
            $this->writeActions();
            $char = $this->io->ask('Enter action command shortcut name', $defaultActionChar);

            if (null === $a = $this->getActionByChar($char)) {
                $this->io->error(sprintf('Invalid command shortcut "%s"', $char));
                sleep(1);
                continue;
            }

            if ($a === $this->helpAction) {
                $this->setVerbose(true);
                continue;
            }

            if ($a === $this->quitAction) {
                $this->io->warning('Exiting per user request.');
                exit;
            }

            return $a;
        }
    }

    /**
     * @param string $char
     *
     * @return null|ActionModel
     */
    private function getActionByChar(string $char): ?ActionModel
    {
        foreach ($this->getCollection()->each() as $action) {
            if ($action->getChar() === $char) {
                return $action;
            }
        }

        return null;
    }

    /**
     * @return ActionHelper
     */
    public function writeActions(): ActionHelper
    {
        if ($this->io->isVerbose()) {
            $this->io->comment('Listing available actions');
            $this->io->newLine();
        }

        $actions = array_map(function (ActionModel $action) {
            return $action->getOutputMarkup($this->verbose);
        }, $this->getContextActions());

        foreach ($actions as $a) {
            $this->io->writeln($a);
        }

        return $this;
    }

    /**
     * @return ActionModel[]
     */
    private function getContextActions(): array
    {
        return array_filter($this->collection->get(), function (ActionModel $action) {
            return false === $action->isHidden() || $this->verbose;
        });
    }

    /**
     * @throws RuntimeException
     *
     * @return void
     */
    private function sanitizeCollection(): void
    {
        $chars = [];

        foreach ($this->getCollection()->each() as $action) {
            $chars[] = $action->getChar();
        }

        if (count(array_unique($chars)) !== count($this->getCollection()->get())) {
            throw new RuntimeException('Duplicate actions characters detected!');
        }
    }

    /**
     * @return void
     */
    abstract protected function setupCollection(): void;
}

<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace RMF\Serferals\Component\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ScanCommand
 */
trait InputOutputAwareTrait
{
    /**
     * @var SymfonyStyle
     */
    protected $style;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param SymfonyStyle $style
     */
    public function setStyle(SymfonyStyle $style)
    {
        $this->style = $style;
    }

    /**
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param \Closure $handler
     */
    protected function ioQ(\Closure $handler)
    {
        if (!$this->io()->isQuiet()) {
            $this->ioClosure($handler);
        }
    }

    /**
     * @param \Closure|null $handler
     *
     * @return mixed|SymfonyStyle
     */
    protected function io(\Closure $handler = null)
    {
        if ($handler !== null) {
            $this->ioClosure($handler);
        }

        return $this->style;
    }

    /**
     * @param \Closure $handler
     */
    protected function ioN(\Closure $handler)
    {
        if (!$this->io()->isVerbose() && !$this->io()->isVeryVerbose()) {
            $this->ioClosure($handler);
        }
    }

    /**
     * @param \Closure $handler
     */
    protected function ioV(\Closure $handler)
    {
        if ($this->io()->isVerbose()) {
            $this->ioClosure($handler);
        }
    }

    /**
     * @param \Closure $handler
     */
    protected function ioVV(\Closure $handler)
    {
        if ($this->io()->isVeryVerbose()) {
            $this->ioClosure($handler);
        }
    }

    /**
     * @param \Closure $handler
     */
    protected function ioClosure(\Closure $handler)
    {
        $handler($this->io(), $this->input, $this->output);
    }
}

/* EOF */

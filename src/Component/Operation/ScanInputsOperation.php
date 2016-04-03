<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace RMF\Serferals\Component\Operation;

use RMF\Serferals\Component\Console\InputOutputAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class ScanInputsOperation
 */
class ScanInputsOperation
{
    use InputOutputAwareTrait;

    /**
     * @var string[]
     */
    protected $paths = [];

    /**
     * @var string[]
     */
    protected $extensions = [];

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @param string[] ...$paths
     *
     * @return $this
     */
    public function paths(...$paths)
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * @param string[] ...$extensions
     *
     * @return $this
     */
    public function extensions(...$extensions)
    {
        $this->extensions = $extensions;

        return $this;
    }

    /**
     * @return Finder
     */
    public function find()
    {
        $finder = Finder::create()
            ->in($this->paths)
            ->files();

        foreach ($this->extensions as $extension) {
            $finder->name('*.'.$extension);
        }

        return $finder;
    }
}
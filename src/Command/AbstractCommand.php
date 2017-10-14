<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Command;

use SR\Console\Output\Style\StyleAwareTrait;
use SR\Serferals\Component\Console\Options\Descriptor\OptionsDescriptor;
use SR\Serferals\Component\Console\StdIO\StdIOTrait;
use Symfony\Component\Console\Command\Command;

class AbstractCommand extends Command
{
    use StyleAwareTrait;
    use StdIOTrait;

    /**
     * @var OptionsDescriptor
     */
    protected $optionsDescriptor;

    /**
     * @param OptionsDescriptor $optionsDescriptor
     */
    public function setOptionsDescriptor(OptionsDescriptor $optionsDescriptor): void
    {
        $this->optionsDescriptor = $optionsDescriptor;
    }
}


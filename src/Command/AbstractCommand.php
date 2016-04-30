<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace RMF\Serferals\Command;

use RMF\Serferals\Component\Console\InputOutputAwareTrait;
use RMF\Serferals\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AbstractCommand
 */
class AbstractCommand extends Command
{
    use InputOutputAwareTrait;

    protected function checkRequirements()
    {
        
    }

    /**
     * @param string $name
     *
     * @return object
     */
    protected function getService($name)
    {
        $getContainerCallable = [$this->getApplication(), 'getContainer'];

        if (!is_callable($getContainerCallable)) {
            $this->writeErrorAndExit('Container getter method not available for application');
        }

        $container = call_user_func($getContainerCallable);

        if (false === ($container instanceof ContainerInterface)) {
            $this->writeErrorAndExit('Invalid container object returned from application.');
        }

        if (!$container->has($name)) {
            $this->writeErrorAndExit(sprintf('Requested service "%s" does not exist', $name));
        }

        return $container->get($name);
    }

    /**
     * @param string $name
     *
     * @returns mixed
     */
    protected function getParameter($name)
    {
        $getContainerCallable = [$this->getApplication(), 'getContainer'];

        if (!is_callable($getContainerCallable)) {
            $this->writeErrorAndExit('Container getter method not available for application');
        }

        $container = call_user_func($getContainerCallable);

        if (false === ($container instanceof ContainerInterface)) {
            $this->writeErrorAndExit('Invalid container object returned from application.');
        }

        if (!$container->hasParameter($name)) {
            $this->writeErrorAndExit(sprintf('Requested parameter "%s" does not exist', $name));
        }

        return $container->getParameter($name);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return $this
     */
    protected function ioSetup(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input);
        $this->setOutput($output);

        $style = $this->getService('rmf.serferals.console_style');

        if ($style instanceof StyleInterface) {
            $this->setStyle($style);
        }

        return $this;
    }

    /**
     * @param bool                $returnMultiple
     * @param string|string[],... $paths
     *
     * @return array[]
     */
    protected function validatePaths($returnMultiple = true, ...$paths)
    {
        $valid = [];
        $invalid = [];

        foreach ($paths as $p) {
            if (false !== ($r = realpath($p)) && is_readable($p) && is_writable($p)) {
                $valid[] = $r;
            } else {
                $invalid[] = $p;
            }
        }

        if ($returnMultiple === false) {
            $valid = array_pop($valid);
            $invalid = array_pop($invalid);
        }

        return [ $valid, $invalid ];
    }

    /**
     * @param int $return
     */
    protected function writeErrorAndExit($message = null, $return = 255)
    {
        if ($this->io() instanceof StyleInterface) {
            $this->io()->error($message ?: 'Exiting script (premature)');
        } else {
            echo sprintf("Error: %s. Exiting with code %d\n", $message, 255);
        }

        exit($return);
    }
}

/* EOF */

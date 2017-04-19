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

use SR\Console\Style\StyleInterface;
use SR\Serferals\Component\Console\InputOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AbstractCommand extends Command
{
    /**
     * @var InputOutput
     */
    protected $io;

    /**
     * @param InputOutput $inputOutput
     */
    public function setInputOutput(InputOutput $inputOutput)
    {
        $this->io = $inputOutput;
    }

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

        return [$valid, $invalid];
    }

    /**
     * @param array $paths
     *
     * @return array
     */
    protected function sanitizePaths(array $paths): array
    {
        return array_map([$this, 'sanitizePath'], $paths);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function sanitizePath(string $path): string
    {
        if (false === ($real = realpath($path))) {
            $this->io->writeCritical('Provided path "%s" does not exist', $path);
        }

        if (false === is_readable($real)) {
            $this->io->writeCritical('Provided path "%s" is not readable', $real);
        }

        if (false === is_writable($real)) {
            $this->io->writeCritical('Provided path "%s" is not writable', $real);
        }

        return $real;
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

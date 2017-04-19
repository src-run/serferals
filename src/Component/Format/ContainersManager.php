<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Format;

use SR\Dumper\YamlDumper;
use SR\Exception\Exception;
use SR\Exception\Runtime\RuntimeException;
use SR\Serferals\Component\Model\Formats\ContainersRuntime;
use SR\Serferals\Component\Model\Formats\SubtitleConfiguration;
use SR\Serferals\Component\Model\Formats\VideoConfiguration;

class ContainersManager
{
    /**
     * @var ContainersRuntime
     */
    private $containers;

    /**
     * @param string $containerPath
     */
    public function __construct(string $containerPath)
    {
        $this->containers = $this->compile($containerPath);
    }

    /**
     * @return ContainersRuntime
     */
    public function getContainers(): ContainersRuntime
    {
        return $this->containers;
    }

    /**
     * @return VideoConfiguration
     */
    public function getVideos(): VideoConfiguration
    {
        return $this->containers->getVideos();
    }

    /**
     * @return SubtitleConfiguration
     */
    public function getSubtitles(): SubtitleConfiguration
    {
        return $this->containers->getSubtitles();
    }

    /**
     * @param string $relative
     *
     * @return ContainersRuntime
     */
    private function compile(string $relative): ContainersRuntime
    {
        try {
            $absolute = $this->getAbsolutePath($relative);
            $compiler = new YamlDumper($absolute, new \DateInterval('P1D'));
            $ymlModel = new ContainersRuntime($compiler->dump()->getData()['containers']);
        } catch (Exception $exception) {
            throw new RuntimeException('Unable to compile container format definitions', $exception);
        }

        return $ymlModel;
    }

    /**
     * @param string $relative
     *
     * @return string
     */
    private function getAbsolutePath(string $relative): string
    {
        clearstatcache(false, $pharPath = 'phar://serferals.phar/'.$relative);

        if (false !== @stat($pharPath)) {
            return $pharPath;
        }

        return __DIR__.DIRECTORY_SEPARATOR.str_repeat('..'.DIRECTORY_SEPARATOR, 3).$relative;
    }
}

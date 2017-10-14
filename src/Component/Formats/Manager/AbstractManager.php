<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Formats\Manager;

use SR\Dumper\DumperInterface;
use SR\Dumper\Exception\CompilationException;
use SR\Dumper\Exception\InvalidInputException;
use SR\Dumper\JsonDumper;
use SR\Dumper\TextDumper;
use SR\Dumper\YamlDumper;
use SR\Exception\Logic\InvalidArgumentException;
use SR\Exception\Runtime\RuntimeException;
use SR\Serferals\Component\Formats\Configuration\AbstractConfiguration;
use SR\Serferals\Component\Formats\Runtime\AbstractRuntime;
use SR\Serferals\Component\Formats\Runtime\LanguageRuntime;
use SR\Serferals\Component\Formats\Runtime\MediaRuntime;
use SR\Serferals\Utilities\ProjectUtilities;

abstract class AbstractManager
{
    /**
     * @var MediaRuntime
     */
    private $configuration;

    /**
     * @param string           $filePath
     * @param ProjectUtilities $project
     */
    public function __construct(string $filePath, ProjectUtilities $project)
    {
        $this->configuration = $this->compile($this->findContainerAbsolutePath($filePath, $project));
    }

    /**
     * @return MediaRuntime|LanguageRuntime|AbstractRuntime
     */
    public function getConfiguration(): AbstractRuntime
    {
        return $this->configuration;
    }

    /**
     * @param string $type
     *
     * @return AbstractConfiguration
     */
    protected function getConfigurationType(string $type): AbstractConfiguration
    {
        $method = sprintf('get%s', ucfirst($type));

        if (!method_exists($this->configuration, $method)) {
            throw new InvalidArgumentException('Invalid format type specified to extensions getter "%s"', $method);
        }

        return $this->configuration->$method();
    }

    /**
     * @param string $absolute
     *
     * @return AbstractRuntime
     */
    private function compile(string $absolute): AbstractRuntime
    {
        try {
            $compile = $this->instantiateDynamicCompile($absolute, new \DateInterval('P1D'));
            $runtime = $this->instantiateDynamicRuntime($absolute, $compile->dump()->getData());
        } catch (InvalidInputException | CompilationException | \Exception $exception) {
            throw new RuntimeException('Error encountered while compiling formats definitions: %s (file "%s")', $exception->getMessage(), $exception, $absolute);
        }

        return $runtime;
    }

    /**
     * @param string        $absolute
     * @param \DateInterval $expiration
     *
     * @return DumperInterface
     */
    private function instantiateDynamicCompile(string $absolute, \DateInterval $expiration): DumperInterface
    {
        switch (pathinfo($absolute, PATHINFO_EXTENSION)) {
            case 'yml':
            case 'yaml':
                return new YamlDumper($absolute, $expiration);

            case 'json':
                return new JsonDumper($absolute, $expiration);

            case 'txt':
            case 'text':
                return new TextDumper($absolute, $expiration);
        }

        throw new RuntimeException('Unable to automatically determine the compiler type from "%s" filename!', $absolute);
    }

    /**
     * @param string $absolute
     * @param array  $data
     *
     * @throws InvalidInputException
     *
     * @return AbstractRuntime
     */
    private function instantiateDynamicRuntime(string $absolute, array $data): AbstractRuntime
    {
        $contextId = pathinfo($absolute, PATHINFO_FILENAME);
        $namespace = preg_replace('{[^\\\]+Runtime$}', '', AbstractRuntime::class);
        $qualified = sprintf('%s%sRuntime', $namespace, ucfirst($contextId));

        if (!isset($data[$contextId])) {
            throw new InvalidInputException('Data file "%s" with runtime "%s" should have a top-level array of name "%s"!', $absolute, $qualified, $contextId);
        }

        return new $qualified($data[$contextId]);
    }

    /**
     * @param string           $filePath
     * @param ProjectUtilities $project
     *
     * @return string
     */
    private function findContainerAbsolutePath(string $filePath, ProjectUtilities $project): string
    {
        clearstatcache(false, $pharPath = 'phar://serferals.phar/'.$filePath);

        if (false !== @stat($pharPath)) {
            return $pharPath;
        }

        return $project->getProjectRootPath().$filePath;
    }

    /**
     * @param string[] ...$names
     *
     * @return string
     */
    private function buildPathFromPathNames(string ...$names): string
    {
        return implode(DIRECTORY_SEPARATOR, $names);
    }
}

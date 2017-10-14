<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Console\Options\Descriptor;

use SR\Console\Output\Style\StyleAwareTrait;
use SR\Serferals\Component\Console\Options\Runtime\FileDeduplicatorOptionsRuntime;
use SR\Serferals\Component\Console\Options\Runtime\FileOrganizerOptionsRuntime;
use SR\Serferals\Component\Console\Options\Runtime\OptionsRuntime;
use SR\Serferals\Component\Console\StdIO\StdIOTrait;

abstract class OptionsDescriptor
{
    use StyleAwareTrait;
    use StdIOTrait;

    /**
     * @param OptionsRuntime|FileDeduplicatorOptionsRuntime|FileOrganizerOptionsRuntime $runtime
     *
     * @return OptionsRuntime|FileDeduplicatorOptionsRuntime|FileOrganizerOptionsRuntime
     */
    public function describe(OptionsRuntime $runtime): OptionsRuntime
    {
        if (!$this->io->isQuiet()) {
            $this->io->section('Runtime Configuration');
            $this->io->tableVertical(...$this->getDescribeTable($runtime));

            if ($this->io->getInput()->isInteractive()) {
                $this->confirmDescribe();
            }
        }

        return $runtime;
    }

    /**
     * @return void
     */
    private function confirmDescribe(): void
    {
        if (!$this->io->confirm('Continue with runtime options detailed above?', true)) {
            $this->writeCommandExit('User elected NOT to accept runtime options shown! Exiting command...');

            exit(224);
        }
    }

    /**
     * @param OptionsRuntime|FileDeduplicatorOptionsRuntime|FileOrganizerOptionsRuntime $runtime
     *
     * @return mixed[]
     */
    abstract protected function getDescribeTable(OptionsRuntime $runtime): array;

    /**
     * @param string $stringNull
     *
     * @return string
     */
    protected function getNullStateMarkup(string $stringNull = 'null'): string
    {
        return $this->getStyledMarkup($stringNull, 'default', 'default');
    }

    /**
     * @param bool   $boolean
     * @param string $stringTrue
     * @param string $stringFalse
     *
     * @return string
     */
    protected function getBooleanStateMarkup(bool $boolean, string $stringTrue = 'enabled', string $stringFalse = 'disabled'): string
    {
        return $this->getStyledMarkup($boolean ? $stringTrue : $stringFalse, $boolean ? 'green' : 'red', null, 'bold');
    }

    /**
     * @param string[] $items
     *
     * @return string
     */
    protected function getArrayListingMarkup(array $items): string
    {
        $size = count($items);
        $list = 0 === $size ? 'NULL' : implode(', ', array_map(function (string $item) {
            return sprintf('"%s"', $item);
        }, $items));

        return
            $this->getStyledMarkup('[', 'black', 'default', 'bold').
            $this->getStyledMarkup(sprintf(' %s ', $list)).
            $this->getStyledMarkup(']', 'black', 'default', 'bold').
            $this->getStyledMarkup(sprintf(' (%d)', $size), 'yellow');
    }

    /**
     * @param string      $string
     * @param string|null $fg
     * @param string|null $bg
     * @param string[]    ...$styles
     *
     * @return string
     */
    protected function getStyledMarkup(string $string, string $fg = null, string $bg = null, string ...$styles): string
    {
        return sprintf('<fg=%s;bg=%s;options=%s>%s</>', $fg ?? 'default', $bg ?? 'default', implode(',', $styles), $string);
    }

    /**
     * @param string[] $headers
     * @param string[] $rows
     *
     * @return array
     */
    protected function prepareTableForVariadicMethod(array $headers, array $rows): array
    {
        return array_map(function ($r) {
            return is_array($r) ? $r : [$r];
        }, array_merge([$headers], $rows));
    }
}

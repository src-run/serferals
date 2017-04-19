<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Console;

use SR\Console\Style\StyleAwareTrait;
use SR\Console\Style\StyleInterface;
use SR\Serferals\Application\SerferalsApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InputOutput
{
    use StyleAwareTrait;

    /**
     * @var int
     */
    private $activeVerbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param StyleInterface  $style
     */
    public function __construct(InputInterface $input, OutputInterface $output, StyleInterface $style)
    {
        $this->setInput($input);
        $this->setOutput($output);
        $this->setStyle($style);
    }

    /**
     * @param int $verbosity
     *
     * @return self
     */
    public function setActiveVerbosity(int $verbosity): self
    {
        $this->activeVerbosity = $verbosity;

        return $this;
    }

    /**
     * @param SerferalsApplication $app
     *
     * @return self
     */
    public function writeTitle(SerferalsApplication $app): self
    {
        $this->io()->applicationTitle(strtolower($app->getName()), $app->getVersion(), $app->getGitHash(), [
            'Author'  => sprintf('%s <%s>', $app->getAuthor(), $app->getAuthorEmail()),
            'License' => $app->getLicense(),
        ]);

        return $this;
    }

    /**
     * @param int $verbosity
     *
     * @return InputOutput
     */
    public function enterState(int $verbosity): self
    {
        $io = clone $this;
        $io->setActiveVerbosity($verbosity);

        return $io;
    }

    /**
     * @param string $message
     *
     * @return InputOutput
     */
    public function writeNotice(string $message): self
    {
        if ($this->verbosityAllowsWrite()) {
        $this->writePrefix('><', 'yellow');
            $this->io()->writeln(sprintf('<fg=yellow>%s</>', $message));
        }

        return $this;
    }

    /**
     * @param string $header
     *
     * @return InputOutput
     */
    public function writeSectionHeader(string $header): self
    {
        $this->style->subSection($header);

        return $this;
    }

    /**
     * @param array $rows
     *
     * @return InputOutput
     */
    public function writeTableRows(array $rows): self
    {
        $this->style->table($rows);

        return $this;
    }

    /**
     * @param string        $message
     * @param bool|null     $default
     * @param \Closure|null $failure
     *
     * @return bool
     */
    public function askConfirm(string $message, bool $default = null, \Closure $failure = null): bool
    {
        $result = $this->style->confirm($message, $default);

        if (false === $result && null !== $failure) {
            $failure($result);
        }

        return $result;
    }

    /**
     * @param string $message
     * @param bool   $warning
     *
     * @return int
     */
    public function writeExit(string $message, bool $warning = false): int
    {
        if ($this->verbosityAllowsWrite()) {
            $this->io()->smallSuccess('EXIT', $message);
        }

        return 0;
    }

    /**
     * @param string $message
     * @param bool   $warning
     *
     * @return int
     */
    public function writeExitWarn(string $message, bool $warning = false): int
    {
        if ($this->verbosityAllowsWrite()) {
            $this->io()->block($message, 'WARN', 'bg=yellow;fg=black', ' ', false);
        }

        return 0;
    }

    /**
     * @param string $message
     *
     * @param array ...$replacements
     */
    public function writeCritical(string $message, ...$replacements)
    {
        $this->io()->error(vsprintf($message, $replacements));

        exit(-1);
    }

    /**
     * @param string $character
     * @param string $color
     */
    private function writePrefix(string $character, string $color = 'white')
    {
        $this->io()->write(vsprintf('<fg=%s;options=reverse> [%s] </> ', [
            $color,
            $character,
        ]));
    }

    /**
     * @return bool
     */
    private function verbosityAllowsWrite()
    {
        return $this->activeVerbosity <= $this->io()->getVerbosity();
    }
}

<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace RMF\Serferals\Component\Console\Style;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\StyleInterface as SymfonyStyleInterface;

/**
 */
interface StyleInterface extends SymfonyStyleInterface, OutputInterface
{
    /**
     * {@inheritdoc}
     */
    public function setVerbosity($level);

    /**
     * {@inheritdoc}
     */
    public function getVerbosity();

    /**
     * {@inheritdoc}
     */
    public function setFormatter(OutputFormatterInterface $formatter);

    /**
     * {@inheritdoc}
     */
    public function getFormatter();

    /**
     * {@inheritdoc}
     */
    public function setDecorated($decorated);

    /**
     * {@inheritdoc}
     */
    public function isDecorated();
    
    /**
     * Returns whether verbosity is quiet (-q).
     *
     * @return bool true if verbosity is set to VERBOSITY_QUIET, false otherwise
     */
    public function isQuiet();

    /**
     * Returns whether verbosity is verbose (-v).
     *
     * @return bool true if verbosity is set to VERBOSITY_VERBOSE, false otherwise
     */
    public function isVerbose();

    /**
     * Returns whether verbosity is very verbose (-vv).
     *
     * @return bool true if verbosity is set to VERBOSITY_VERY_VERBOSE, false otherwise
     */
    public function isVeryVerbose();

    /**
     * Returns whether verbosity is debug (-vvv).
     *
     * @return bool true if verbosity is set to VERBOSITY_DEBUG, false otherwise
     */
    public function isDebug();

    /**
     * Formats a message as a block of text.
     *
     * @param string|array $messages The message to write in the block
     * @param string|null $type The block type (added in [] on first line)
     * @param string|null $style The style to apply to the whole block
     * @param string $prefix The prefix for the block
     * @param bool $padding Whether to add vertical padding
     */
    public function block($messages, $type = null, $style = null, $prefix = ' ', $padding = false);
    
    /**
     * {@inheritdoc}
     */
    public function comment($message, $newLine = true);

    /**
     * {@inheritdoc}
     */
    public function writeln($messages, $type = self::OUTPUT_NORMAL);

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL);

    /**
     * {@inheritdoc}
     */
    public function newLine($count = 1);

    /**
     * @param string        $question
     * @param null|string   $default
     * @param null          $validator
     * @param null|\Closure $sanitizer
     *
     * @return mixed
     */
    public function ask($question, $default = null, $validator = null, $sanitizer = null);
}

/* EOF */

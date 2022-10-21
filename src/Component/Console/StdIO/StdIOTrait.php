<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Console\StdIO;

use SR\Console\Output\Helper\BlockHelper;
use SR\Console\Output\Style\StyleInterface;

trait StdIOTrait
{
    /**
     * @var string
     */
    protected static $stdIOWriteTypeText = 'text';

    /**
     * @var string
     */
    protected static $stdIOWriteTypeComment = 'comment';

    /**
     * @var string
     */
    protected static $stdIOWriteTypeTitle = 'title';

    /**
     * @var string
     */
    protected static $stdIOWriteTypeSection = 'section';

    /**
     * @var string
     */
    protected static $stdIOWriteTypeSubSection = 'subSection';

    /**
     * @var string
     */
    protected static $stdIOWriteTypeInfo = 'info';

    /**
     * @var string
     */
    protected static $stdIOWriteTypeSuccess = 'success';

    /**
     * @var string
     */
    protected static $stdIOWriteTypeWarning = 'warning';

    /**
     * @var string
     */
    protected static $stdIOWriteTypeError = 'error';

    /**
     * @var string
     */
    protected static $stdIOWriteTypeCritical = 'critical';

    /**
     * @var string
     */
    protected static $stdIOWriteTypeBlock = 'block';

    /**
     * @param string $message
     */
    protected function writeNote(string $message): void
    {
        $this->writeMessageType($message, static::$stdIOWriteTypeBlock, 'NOTE', BlockHelper::TYPE_MD, '--', 'black', 'yellow');
    }

    /**
     * @param string $message
     * @param string ...$replacements
     *
     * @return void
     */
    protected function writeWarning(string $message, string ...$replacements): void
    {
        $this->writeMessageType(sprintf($message, ...$replacements), static::$stdIOWriteTypeWarning);
    }

    /**
     * @param string $message
     */
    protected function writeError(string $message = null)
    {
        $this->writeMessageType($this->compileWriteMessage('Command "%s" encountered an undefined error!', $message), static::$stdIOWriteTypeError);
    }

    /**
     * @param string $message
     */
    protected function writeCritical(string $message = null)
    {
        $this->writeMessageType($this->compileWriteMessage('Command "%s" encountered a critical undefined error!', $message), static::$stdIOWriteTypeCritical);
    }

    /**
     * @param string $message
     */
    protected function writeSuccess(string $message = null)
    {
        $this->writeMessageType($this->compileWriteMessage('Command "%s" completed a successful operation!', $message), static::$stdIOWriteTypeSuccess);
    }

    /**
     * @param string $message
     * @param int    $return
     */
    protected function writeHaltingError(string $message = null, int $return = 255)
    {
        $this->writeMessageType($this->compileWriteMessage('Command "%s" encountered an undefined error causing the script to halt.', $message), static::$stdIOWriteTypeCritical);

        exit($return);
    }

    /**
     * @param string      $message
     * @param string|null $type
     * @param mixed       ...$arguments
     */
    protected function writeMessageType(string $message, string $type = null, ...$arguments): void
    {
        $type = $type ?? static::$stdIOWriteTypeComment;

        if (property_exists($this, 'io') && $this->io instanceof StyleInterface && method_exists($this->io, $type)) {
            call_user_func([$this->io, $type], $message, ...$arguments);
        } else {
            printf('[%s] %s', strtoupper($type), $message);
        }
    }

    /**
     * @param string $message
     */
    protected function writeCommandExit(string $message): void
    {
        $this->writeMessageType($message, static::$stdIOWriteTypeBlock, 'EXIT', BlockHelper::TYPE_MD, '##', 'black', 'yellow');
    }

    /**
     * @param string|null $message
     * @param int         $return
     *
     * @return int
     */
    protected function writeCommandCompletionFailure(string $message = null, int $return = 255): int
    {
        $this->writeMessageType($message ? sprintf('Error: %s', $message) : $this->compileWriteReplacements('Command "%s" operations failed in an undefined state.', $this->getName()),
            static::$stdIOWriteTypeBlock, 'EXIT', BlockHelper::TYPE_MD, null, 'white', 'red');

        return $return;
    }

    /**
     * @param string|null $message
     * @param int         $return
     *
     * @return int
     */
    protected function writeCommandCompletionSuccess(string $message = null, int $return = 0): int
    {
        $this->writeSuccess($message ?? $this->compileWriteReplacements('Command "%s" operations completed successfully!', $this->getName()));

        return $return;
    }

    /**
     * @return string
     */
    private function getWriteContext(): string
    {
        if (method_exists($this, 'getName')) {
            return $this->getName();
        }

        return get_called_class();
    }

    /**
     * @param string      $defaultMessage
     * @param string|null $message
     * @param array       ...$replacements
     *
     * @return string
     */
    private function compileWriteMessage(string $defaultMessage, string $message = null, ...$replacements): string
    {
        if (null === $message) {
            return $this->compileWriteReplacements($defaultMessage, ...array_merge([$this->getWriteContext()], $replacements));
        }

        return $this->compileWriteReplacements($message, ...$replacements);
    }

    /**
     * @param string $format
     * @param array  ...$replacements
     *
     * @return string
     */
    private function compileWriteReplacements(string $format, ...$replacements): string
    {
        if (false !== $compiled = @vsprintf($format, $replacements)) {
            return $compiled;
        }

        return $format;
    }
}

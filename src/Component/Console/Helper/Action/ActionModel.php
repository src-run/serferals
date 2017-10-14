<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Console\Helper\Action;

use SR\Exception\Logic\InvalidArgumentException;

final class ActionModel
{
    /**
     * @var string
     */
    private $char;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $desc;

    /**
     * @var bool
     */
    private $hidden;

    /**
     * @var ActionCollection
     */
    private $collection;

    /**
     * @param string      $char
     * @param string      $name
     * @param string|null $desc
     * @param bool        $hidden
     */
    public function __construct(string $char, string $name, string $desc = null, bool $hidden = true)
    {
        $this->setChar($char);
        $this->setName($name);
        $this->setDesc($desc);
        $this->setHidden($hidden);
    }

    /**
     * @param string $char
     *
     * @return self
     */
    public function setChar(string $char): self
    {
        if (mb_strlen($char) > 1) {
            throw new InvalidArgumentException('Char value must be only one character in length but passed string "%s" is "%d" characters', $char, mb_strlen($char));
        }

        $this->char = $char;

        return $this;
    }

    /**
     * @return string
     */
    public function getChar(): string
    {
        return $this->char;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string|null $desc
     *
     * @return self
     */
    public function setDesc(string $desc = null): self
    {
        $this->desc = $desc;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getDesc(): ?string
    {
        return $this->desc;
    }

    /**
     * @return int
     */
    public function getDescPadding(): int
    {
        $len = 0;

        foreach ($this->collection->each() as $action) {
            if (strlen($action->getName()) > $len) {
                $len = strlen($action->getName());
            }
        }

        $pad = $len - strlen($this->getName()) + 1;

        return $pad > 1 ? $pad : 1;
    }

    /**
     * @return bool
     */
    public function hasDesc(): bool
    {
        return null !== $this->desc;
    }

    /**
     * @param bool $hidden
     *
     * @return self
     */
    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param ActionCollection $collection
     *
     * @return self
     */
    public function setCollection(ActionCollection $collection): self
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasCollection(): bool
    {
        return null !== $this->collection;
    }

    /**
     * @param bool $verbose
     *
     * @return string
     */
    public function getOutputMarkup(bool $verbose = false): string
    {
        $markup = sprintf(' [ <em>%s</em> ] %s', $this->char, $this->name);

        if (true === $verbose && null !== $this->desc) {
            $markup .= sprintf('%s<comment>%s</comment>', str_repeat(' ', $this->getDescPadding()), strtolower($this->desc));
        }

        return $markup;
    }
}

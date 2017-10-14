<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Formats\Model;

class LanguageISO639Format extends AbstractFormat
{
    /**
     * @var string
     */
    private $iso6391;

    /**
     * @var string
     */
    private $iso6392B;

    /**
     * @var string
     */
    private $iso6392T;

    /**
     * @var string[]
     */
    private $iso6393;

    /**
     * @var string
     */
    private $family;

    /**
     * @var string
     */
    private $nativeName;

    /**
     * @param string $name
     */
    public function __construct(string $iso6391, string $iso6392B, string $iso6392T, array $iso6393, string $family, string $name, string $nativeName)
    {
        parent::__construct($name);

        $this->iso6391 = $iso6391;
        $this->iso6392B = $iso6392B;
        $this->iso6392T = $iso6392T;
        $this->iso6393 = $iso6393;
        $this->family = $family;
        $this->nativeName = $nativeName;
    }

    /**
     * @return string
     */
    public function getISO6391(): string
    {
        return $this->iso6391;
    }

    /**
     * @return string
     */
    public function getISO6392B(): string
    {
        return $this->iso6392B;
    }

    /**
     * @return string
     */
    public function getISO6392T(): string
    {
        return $this->iso6392T;
    }

    /**
     * @return string[]
     */
    public function getISO6393(): array
    {
        return $this->iso6393;
    }

    /**
     * @return string
     */
    public function getFamily(): string
    {
        return $this->family;
    }

    /**
     * @return string
     */
    public function getNativeName(): string
    {
        return $this->nativeName;
    }
}

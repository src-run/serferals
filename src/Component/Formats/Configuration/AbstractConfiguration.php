<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Formats\Configuration;

use SR\Exception\Runtime\RuntimeException;
use SR\Serferals\Component\Formats\Model\AbstractFormat;
use SR\Serferals\Component\Formats\Model\LanguageISO639Format;
use SR\Serferals\Component\Formats\Model\SubtitleFormat;
use SR\Serferals\Component\Formats\Model\VideoFormat;

abstract class AbstractConfiguration
{
    /**
     * @var AbstractFormat[]
     */
    private $elements;

    /**
     * @var string|null
     */
    private $targetId;

    /**
     * @param array       $elements
     * @param string|null $targetId
     */
    public function __construct(array $elements, string $targetId = null)
    {
        $this->targetId = $targetId;
        $this->elements = array_map(function (array $data) {
            return $this->hydrateFormat($data);
        }, $this->sanitizeFormatData($elements));
    }

    /**
     * @return AbstractFormat[]|SubtitleFormat[]|VideoFormat[]|LanguageISO639Format[]
     */
    public function all(): array
    {
        return $this->elements;
    }

    /**
     * @param \Closure $closure
     *
     * @return AbstractFormat[]|SubtitleFormat[]|VideoFormat[]|LanguageISO639Format[]
     */
    public function filterAll(\Closure $closure): array
    {
        $formats = array_filter($this->all(), function (AbstractFormat $format) use ($closure) {
            return true === $closure($format);
        });

        return $this->sortElementsNatural($formats);
    }

    /**
     * @return \Generator|AbstractFormat[]|SubtitleFormat[]|VideoFormat[]|LanguageISO639Format[]
     */
    public function each(): \Generator
    {
        foreach ($this->all() as $format) {
            yield $format;
        }
    }

    /**
     * @param \Closure $closure
     *
     * @return \Generator|AbstractFormat[]|SubtitleFormat[]|VideoFormat[]|LanguageISO639Format[]
     */
    public function filterEach(\Closure $closure): \Generator
    {
        foreach ($this->all() as $f) {
            if (true === $closure($f)) {
                yield $f;
            }
        }
    }

    /**
     * @param string[]|AbstractFormat[] $formats
     *
     * @return string[]|AbstractFormat[]
     */
    protected function sortElementsNatural(array $formats): array
    {
        sort($formats);

        return $formats;
    }

    /**
     * @return string
     */
    private function getFormatType(): string
    {
        if (1 !== preg_match('{^(?<namespace>.+)\\\Configuration\\\(?<target>.+)Configuration}i', get_called_class(), $matches)) {
            throw new RuntimeException('Unable to determine the format qualified class name for the "%s" configuration.', get_called_class());
        }

        return sprintf('%s\Model\%sFormat', $matches['namespace'], ucfirst($this->targetId ?? $matches['target']));
    }

    /**
     * @param array $format
     *
     * @return AbstractFormat
     */
    private function hydrateFormat(array $format): AbstractFormat
    {
        $qualified = $this->getFormatType();

        return new $qualified(...$format);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function sanitizeFormatData(array $data): array
    {
        array_walk($data, function (&$v) {
            uksort($v, function ($a, $b) { return $a > $b; });
            $v = array_values($v);
        });

        return $data;
    }
}

<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Model\Formats;

abstract class AbstractConfiguration
{
    /**
     * @var AbstractFormat[]
     */
    protected $formats;

    /**
     * @param array $formats
     */
    public function __construct(array $formats)
    {
        $this->formats = array_map([$this, 'hydrateFormat'], $this->sanitizeFormatData($formats));
    }

    /**
     * @param string $extension
     *
     * @return null|AbstractFormat|VideoFormat|SubtitleFormat
     */
    public function findExtension(string $extension): ?AbstractFormat
    {
        foreach ($this->formats as $f) {
            if ($f->hasExtension($extension)) {
                return $f;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getExtensions(): array
    {
        $extensions = [];
        foreach ($this->formats as $f) {
            $extensions = array_merge($extensions, $f->getExtensions());
        }
        sort($extensions);

        return $extensions;
    }

    /**
     * @return string
     */
    abstract protected function getFormatType(): string;

    /**
     * @param array $format
     *
     * @return AbstractFormat
     */
    protected function hydrateFormat(array $format): AbstractFormat
    {
        $model = $this->getFormatType();

        return new $model(...$format);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function sanitizeFormatData(array $data): array
    {
        array_walk($data, function (&$v) {
            uksort($v, function ($a, $b) {
                return $a > $b;
            });

            $v = array_values($v);
        });

        return $data;
    }
}

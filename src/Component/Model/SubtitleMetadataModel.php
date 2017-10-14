<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Model;

use SR\Spl\File\SplFileInfo as FileInfo;

class SubtitleMetadataModel implements \Serializable
{
    use MetadataModelTrait;

    /**
     * @var string
     */
    const TYPE = 'subtitle';

    /**
     * @var string
     */
    protected $language;

    /**
     * @var float
     */
    protected $similarity;

    /**
     * @return string[]
     */
    public function getFieldsStatic()
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getFieldsEditable()
    {
        return [
            'enabled' => 'Enabled',
            'language' => 'Language',
            'year' => 'Year',
        ];
    }

    /**
     * @param FileInfo    $file
     * @param float|null  $similarity
     * @param string|null $language
     * @param bool        $enabled
     *
     * @return $this
     */
    public static function create(FileInfo $file, $similarity = null, $language = null, $enabled = false): self
    {
        $instance = static::newInstance($enabled)
            ->setFile($file)
            ->setSimilarity($similarity)
            ->setLanguage($language);

        return $instance;
    }

    /**
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @return bool
     */
    public function hasLanguage(): bool
    {
        return null !== $this->language;
    }

    /**
     * @param string|null $language
     *
     * @return $this
     */
    public function setLanguage(string $language = null): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param float $similarity
     *
     * @return self
     */
    public function setSimilarity(float $similarity): self
    {
        $this->similarity = $similarity;

        return $this;
    }

    /**
     * @return float
     */
    public function getSimilarity(): float
    {
        return $this->similarity;
    }

    /**
     * @return bool
     */
    public function hasSimilarity(): bool
    {
        return null !== $this->similarity;
    }

    /**
     * @return bool
     */
    public function isSimilarityExact(): bool
    {
        return 100 === $this->similarity;
    }
}


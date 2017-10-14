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

use SR\Dumper\Exception\InvalidInputException;
use SR\Spl\File\SplFileInfo as FileInfo;

class MediaMetadataModel implements \Serializable
{
    use MetadataModelTrait;

    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var int|null
     */
    protected $year;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var
     */
    protected $subtitles;

    /**
     * @var int
     */
    protected $activeSubtitle;

    /**
     * @return string[]
     */
    public function getFieldsStatic()
    {
        return [
            'file' => 'File Path',
        ];
    }

    /**
     * @return string[]
     */
    public function getFieldsEditable()
    {
        return [
            'enabled' => 'Enabled',
            'name' => 'Name',
            'year' => 'Year',
        ];
    }

    /**
     * @param FileInfo $file
     * @param string   $name
     * @param bool     $enabled
     *
     * @return $this
     */
    public static function create(FileInfo $file, $name = null, $enabled = false)
    {
        $instance = static::newInstance($enabled)
            ->setFile($file)
            ->setName($name);

        return $instance;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasId()
    {
        return $this->id !== null;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param int|null $year
     *
     * @return $this
     */
    public function setYear($year)
    {
        $this->year = (int) $year;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasYear()
    {
        return $this->year !== null;
    }

    /**
     * @return SubtitleMetadataModel[]
     */
    public function getSubtitles(): ?array
    {
        return $this->subtitles;
    }

    /**
     * @return bool
     */
    public function hasSubtitles(): bool
    {
        return 0 !== count($this->subtitles);
    }

    /**
     * @param SubtitleMetadataModel[] ...$subtitles
     *
     * @return MediaMetadataModel
     */
    public function setSubtitles(SubtitleMetadataModel ...$subtitles): self
    {
        $this->subtitles = $subtitles;

        return $this;
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    public function setActiveSubtitle(int $index): bool
    {
        if (array_key_exists($index, $this->subtitles)) {
            $this->activeSubtitle = $index;

            return true;
        }

        return false;
    }

    /**
     * @return null|SubtitleMetadataModel
     */
    public function getActiveSubtitle(): ?SubtitleMetadataModel
    {
        return $this->hasActiveSubtitle() ? $this->subtitles[$this->activeSubtitle] : null;
    }

    /**
     * @return bool
     */
    public function hasActiveSubtitle(): bool
    {
        return null !== $this->activeSubtitle && isset($this->subtitles[$this->activeSubtitle]);
    }

    /**
     * @return int|null
     */
    public function getActiveSubtitleIndex(): ?int
    {
        return $this->activeSubtitle;
    }
}


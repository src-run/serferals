<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Tasks\Metadata;

use SR\Console\Output\Style\StyleAwareTrait;
use SR\Serferals\Component\Formats\Manager\LanguageManager;
use SR\Serferals\Component\Formats\Model\LanguageISO639Format;
use SR\Serferals\Component\Model\MediaMetadataModel;
use SR\Serferals\Component\Model\SubtitleMetadataModel;
use SR\Serferals\Component\Tasks\Filesystem\FinderGeneratorTask;
use SR\Spl\File\SplFileInfo;

class FileSubtitleAssociateTask
{
    use StyleAwareTrait;

    /**
     * @var FinderGeneratorTask
     */
    protected $finderGenerator;

    /**
     * @var bool
     */
    protected $disabled = false;

    /**
     * @var string[]
     */
    protected $extensions = [];

    /**
     * @var LanguageManager
     */
    private $languageManager;

    /**
     * @param LanguageManager $languageManager
     */
    public function __construct(LanguageManager $languageManager)
    {
        $this->languageManager = $languageManager;
    }

    /**
     * @param FinderGeneratorTask $finderGenerator
     *
     * @return $this
     */
    public function setFinderGenerator(FinderGeneratorTask $finderGenerator)
    {
        $this->finderGenerator = $finderGenerator;

        return $this;
    }

    /**
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * @param string[] $extensions
     *
     * @return $this
     */
    public function setExtensions(string ...$extensions): self
    {
        $this->extensions = $extensions;

        return $this;
    }

    /**
     * @param MediaMetadataModel[] ...$media
     *
     * @return MediaMetadataModel[]
     */
    public function execute(MediaMetadataModel ...$media)
    {
        return array_map(function (MediaMetadataModel $m) {
            return $this->attachSubtitles($m);
        }, $media);
    }

    /**
     * @param MediaMetadataModel $media
     *
     * @return MediaMetadataModel
     */
    private function attachSubtitles(MediaMetadataModel $media): MediaMetadataModel
    {
        if (0 !== count($subtitles = $this->getSubtitles($media))) {
            $subtitles[0]->setEnabled(true);
            $media->setSubtitles(...$subtitles);
            $media->setActiveSubtitle($this->getActiveSubtitle(...$subtitles));
        }

        return $media;
    }

    /**
     * @param SubtitleMetadataModel[] ...$subtitles
     *
     * @return int
     */
    private function getActiveSubtitle(SubtitleMetadataModel ...$subtitles): int
    {
        $selection = 0;

        foreach ($subtitles as $i => $s) {
            if ($s->getLanguage() === 'eng') {
                $selection = $i;
                break;
            }
        }

        return $selection;
    }

    /**
     * @param MediaMetadataModel $media
     *
     * @return SubtitleMetadataModel[]|null
     */
    private function getSubtitles(MediaMetadataModel $media): array
    {
        return $this->hydrateSubtitleModels($media, ...$this->findSubtitleFiles($media)) ?? null;
    }

    /**
     * @param MediaMetadataModel $media
     *
     * @return \SplFileInfo[]
     */
    private function findSubtitleFiles(MediaMetadataModel $media): array
    {
        $finder = $this
            ->finderGenerator
            ->reset()
            ->in($media->getFile()->getPath())
            ->extensions(...$this->extensions);

        return array_values(iterator_to_array($finder->find()));
    }

    /**
     * @param MediaMetadataModel $media
     * @param \SplFileInfo[]     ...$files
     *
     * @return SubtitleMetadataModel[]
     */
    private function hydrateSubtitleModels(MediaMetadataModel $media, \SplFileInfo ...$files): array
    {
        $subtitles = array_map(function (\SplFileInfo $file) use ($media) {
            return $this->hydrateModel($media, $file);
        }, $files);

        usort($subtitles, function (SubtitleMetadataModel $a, SubtitleMetadataModel $b) use ($media) {
            return $a->getSimilarity() < $b->getSimilarity();
        });

        return $subtitles;
    }

    private function hydrateModel(MediaMetadataModel $media, \SplFileInfo $file): SubtitleMetadataModel
    {
        $model = SubtitleMetadataModel::create(new SplFileInfo($file->getPathname()), $this->getFileNameSimilarity($file, $media->getFile()));

        if (null !== $language = $this->detectSubtitleLanguage($model)) {
            $model->setLanguage($language);
        }

        return $model;
    }

    /**
     * @param SubtitleMetadataModel $subtitle
     *
     * @return string|null
     */
    private function detectSubtitleLanguage(SubtitleMetadataModel $subtitle): ?string
    {
        foreach ($this->languageManager->getISO639Language()->each() as $language) {
            if (true === $this->isLanguageMatch($subtitle, $language)) {
                return $language->getISO6392T();
            }
        }

        return null;
    }

    /**
     * @param SubtitleMetadataModel $subtitle
     * @param LanguageISO639Format  $language
     *
     * @return bool
     */
    private function isLanguageMatch(SubtitleMetadataModel $subtitle, LanguageISO639Format $language)
    {
        $search = implode('|', array_map(function (string $language) {
            return preg_quote($language);
        }, [$language->getISO6391(), $language->getISO6392B(), $language->getISO6392T()]));

        return 1 === preg_match(sprintf('{\.(%s)\.%s$}i', $search, preg_quote($subtitle->getFile()->getExtension())), $subtitle->getFile()->getBasename());
    }

    /**
     * @param \SplFileInfo $a
     * @param \SplFileInfo $b
     * @param bool         $fileNameOnly
     *
     * @return float
     */
    private function getFileNameSimilarity(\SplFileInfo $a, \SplFileInfo $b, bool $fileNameOnly = true): float
    {
        similar_text(pathinfo($a->getBasename(), PATHINFO_FILENAME),
            pathinfo($b->getBasename(), PATHINFO_FILENAME), $similarity);

        return $similarity;
    }
}


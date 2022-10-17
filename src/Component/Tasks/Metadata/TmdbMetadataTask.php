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
use SR\Console\Output\Style\StyleInterface;
use SR\Serferals\Component\Console\Helper\MetadataActionHelper;
use SR\Serferals\Component\Model\MediaMetadataModel;
use SR\Serferals\Component\Model\EpisodeMetadataModel;
use SR\Serferals\Component\Model\MovieMetadataModel;
use SR\Serferals\Component\Model\SubtitleMetadataModel;
use SR\Serferals\Component\Tmdb\EpisodeResolver;
use SR\Serferals\Component\Tmdb\MovieResolver;
use Tmdb\Model\AbstractModel;
use Tmdb\Model\Collection\ResultCollection;
use Tmdb\Model\Movie;
use Tmdb\Model\Tv;

class TmdbMetadataTask
{
    use StyleAwareTrait;

    /**
     * @var FileMetadataTask
     */
    protected $fileMetadata;

    /**
     * @var MetadataActionHelper
     */
    protected $metadataActionHelper;

    /**
     * @var EpisodeResolver
     */
    protected $episodeResolver;

    /**
     * @var MovieResolver
     */
    protected $movieResolver;

    /**
     * @var bool
     */
    protected $skipFailures;

    /**
     * @param FileMetadataTask     $fileMetadata
     * @param MetadataActionHelper $metadataActionHelper
     * @param EpisodeResolver      $episodeResolver
     * @param MovieResolver        $movieResolver
     */
    public function __construct(FileMetadataTask $fileMetadata, MetadataActionHelper $metadataActionHelper, EpisodeResolver $episodeResolver, MovieResolver $movieResolver)
    {
        $this->fileMetadata = $fileMetadata;
        $this->metadataActionHelper = $metadataActionHelper;
        $this->episodeResolver = $episodeResolver;
        $this->movieResolver = $movieResolver;
    }

    /**
     * @param $skipFailures
     *
     * @return $this
     */
    public function setSkipFailures($skipFailures)
    {
        $this->skipFailures = $skipFailures;

        return $this;
    }

    /**
     * @param MediaMetadataModel[] $fixtureSet
     *
     * @return MediaMetadataModel[]|EpisodeMetadataModel[]|MovieMetadataModel[]
     */
    public function resolve(array $fixtureSet)
    {
        $i = 0;
        $c = count($fixtureSet);

        if (0 === $c) {
            $this->io
                ->environment(StyleInterface::VERBOSITY_VERBOSE)
                ->subSection('File API Resolutions');
        }

        $fixtureSet = array_map(
            function (MediaMetadataModel $f) use ($c, &$i) {
                static $skip = null;
                if ($skip === true) {
                    $f->setEnabled(false);

                    return $f;
                }

                return $this->lookup($f, $c, $i, $skip);
            },
            $fixtureSet
        );

        return array_filter($fixtureSet, function (MediaMetadataModel $fixture) {
            return $fixture->isEnabled();
        });
    }

    /**
     * @param MediaMetadataModel $f
     * @param int         $count
     * @param int         $i
     * @param bool        $skipRemaining
     *
     * @return MediaMetadataModel|EpisodeMetadataModel|MovieMetadataModel
     */
    public function lookup(MediaMetadataModel $f, $count, &$i, &$skipRemaining)
    {
        ++$i;
        $mode = $f::TYPE;
        $lookupSelection = 1;

        while (true) {
            $this->io->section(sprintf('%03d of %03d', $i, $count));

            if (!file_exists($f->getFile()->getPathname())) {
                $this->io->error(sprintf('File no longer exists: %s', $f->getFile()->getPathname()));
                break;
            }

            if ($mode === MovieResolver::TYPE) {
                if ($f instanceof EpisodeMetadataModel) {
                    $f = $this->fileMetadata->parseFileAsMovie($f->getFile());
                }

                $results = $this->movieResolver->resolve($f)->getResults();
                $resultSelected = $this->getResultSelection($results, $lookupSelection);
                $item = $this->getResultSelection($results, $lookupSelection);
            } else {
                if ($f instanceof MovieMetadataModel) {
                    $f = $this->fileMetadata->parseFileAsEpisode($f->getFile());
                }

                $results = $this->episodeResolver->resolve($f)->getResults();
                $resultSelected = $this->getResultSelection($results, $lookupSelection);
                $item = $this->episodeResolver->resolveSingle($f, $resultSelected);
            }

            if ($results->count() == 0 || !$item) {
                $this->writeLookupFailure($f);
            } else {
                $this->writeLookupSuccess($f, $item, $resultSelected);
            }

            if ($this->skipFailures === true && ($results->count() == 0 || !$item)) {
                $this->io->warning('Skipping: Option enabled for API lookup failures to be auto-skip.');
                break;
            }

            $a = $this->metadataActionHelper->writeActionsAndGetResult($this->getDefaultItemAction($f, $results, $item));

            switch ($a->getChar()) {
                case 'c':
                    $this->io->comment('Adding item to queue and continuing...');
                    $this->hydrateFixture($f, $item, $this->getResultSelection($results, $lookupSelection));
                    break 2;

                case 'C':
                    $this->io->comment('Adding forced item to queue and continuing...');
                    $f->setEnabled(true);
                    break 2;

                case 'e':
                    $this->editFixture($f);
                    break;

                case 'l':
                    $lookupSelection = $this->listResults($results);
                    break;

                case 's':
                    $f->setEnabled(false);
                    $this->io->comment('Skipping...');
                    break 2;

                case 'r':
                    $f->setEnabled(false);
                    $removeResult = $this->remove($f);
                    $this->io->newLine();

                    if ($removeResult === 1) {
                        break 1;
                    } else {
                        break 2;
                    }

                case 'm':
                    $mode = ($mode === EpisodeResolver::TYPE ? MovieResolver::TYPE : EpisodeResolver::TYPE);
                    $this->io->comment(sprintf(
                        'Lookup mode switched to "%s"',
                        $mode
                    ));
                    break;

                case 'D':
                    $skipRemaining = true;
                    break 2;

                case 't':
                    if ($f->hasSubtitles()) {
                        $f->getSubtitles()[0]->setEnabled(!$f->getSubtitles()[0]->getEnabled());
                    }
                    continue 2;

                case 'T':
                    $this->editFixtureSubtitle($f);
                    continue 2;
            }
        }

        return $f;
    }

    /**
     * @param MediaMetadataModel $file
     * @param ResultCollection   $results
     * @param \Tmdb\Model\AbstractModel|Tv\Episode
     *
     * @return string
     */
    private function getDefaultItemAction(MediaMetadataModel $file, ResultCollection $results, $item): string
    {
        try {
            if ($file->getFile()->getSize() < 40000000) {
                $this->io->warning('File is likely a ancillary file (sample, trailer, etc). Marking for removal!');
                $actionDefault = 'r';
            } else {
                $actionDefault = $results->count() == 0 || !$item ? 's' : 'c';
            }
        } catch (\RuntimeException $e) {
            $actionDefault = $results->count() == 0 || !$item ? 's' : 'c';
        }

        return $actionDefault;
    }

    /**
     * @param ResultCollection $resultSet
     *
     * @return int
     */
    private function listResults(ResultCollection $resultSet)
    {
        $tableRows = array_values(array_map(
            function (AbstractModel $m) {
                static $i = 0;
                $overviewMax = 80;
                $overview = '';
                $country = '';

                if ($m instanceof Tv) {
                    foreach(explode(' ', $m->getOverview()) as $overviewWord) {
                        if (mb_strlen($overview) + mb_strlen($overviewWord) > $overviewMax) {
                            $overview = sprintf('%s...', rtrim($overview));
                            break;
                        }

                        $overview .= sprintf('%s ', $overviewWord);
                    }

                    $countrySet = $m->getOriginCountry();

                    if ($countrySet->count() > 0) {
                        $countryKey = $countrySet->getKeys()[0];
                        $country = $countrySet->get($countryKey)->getIso31661();
                    }

                    return ['['.++$i.'] '.$m->getId(), $m->getName(), $m->getFirstAirDate()->format('Y\-m\-d'), $country ?: '', $overview];
                }

                if ($m instanceof Movie) {
                    foreach(explode(' ', $m->getOverview()) as $overviewWord) {
                        if (mb_strlen($overview) + mb_strlen($overviewWord) > $overviewMax) {
                            $overview = sprintf('%s...', rtrim($overview));
                            break;
                        }

                        $overview .= sprintf('%s ', $overviewWord);
                    }

                    $countrySet = $m->getProductionCountries();

                    if ($countrySet->count() > 0) {
                        $countryKey = $countrySet->getKeys()[0];
                        $country = $countrySet->get($countryKey)->getIso31661();
                    }

                    return ['['.++$i.'] '.$m->getId(), $m->getTitle(), $m->getReleaseDate()->format('Y\-m\-d'), $country ?: '', $overview];
                }

                return null;
            },
            $resultSet->getAll()
        ));

        array_filter($tableRows, function ($row) {
            return $row !== null;
        });

        $this->io
            ->environment(StyleInterface::VERBOSITY_VERBOSE)
            ->comment('Listing Tvdb lookup search results');

        $this->io->table(['[#] Tvdb Id', 'Title', 'Release Date', 'Country', 'Description'], ...$tableRows);
        $selection = $this->io->ask('Enter result item number', 1, null, function ($value) {
            return (int) $value;
        });

        return (int) $selection;
    }

    /**
     * @param MediaMetadataModel $f
     */
    private function editFixture(MediaMetadataModel $f)
    {
        $this->io
            ->environment(StyleInterface::VERBOSITY_VERBOSE)
            ->comment('Listing fixture property values');

        while (true) {
            list($tableHeads, $tableRows, $control) = $this->getEditFixtureTable($f);
            $this->io->table($tableHeads, ...$tableRows);
            $action = strtolower($this->io->ask('Enter value number or no value to exit editor', 'exit'));

            switch ($action) {
                case 'done':
                case 'exit':
                    break 2;

                default:
                    $this->editFixtureProperty($f, $action, $control);
            }
        }
    }

    /**
     * @param MediaMetadataModel $f
     *
     * @return array
     */
    private function getEditFixtureTable(MediaMetadataModel $f)
    {
        $tableRows = [];
        $control = [];
        $i = 0;

        foreach ($f->getFieldsEditable() as $property => $name) {
            $control[] = [$property, $name];
            $tableRows[] = $this->getEditFixtureTableRow($f, $property, $name, $i, true);
        }

        foreach ($f->getFieldsStatic() as $property => $name) {
            $tableRows[] = $this->getEditFixtureTableRow($f, $property, $name, $i, false);
        }

        return [
            ['[#] Field', 'Value'],
            $tableRows,
            $control,
        ];
    }

    /**
     * @param MediaMetadataModel $f
     * @param string      $property
     * @param string      $name
     * @param int         $i
     * @param bool        $editable
     *
     * @return array
     */
    private function getEditFixtureTableRow(MediaMetadataModel $f, $property, $name, &$i, $editable)
    {
        $index = $editable === true ? sprintf('[%d] %s', ($i++), $name) : sprintf('[-] %s', $name);
        $method = 'get'.ucfirst($property);
        $result = call_user_func([$f, $method]);

        if (is_bool($result)) {
            $value = ($result === true ? 'yes' : 'no');
        } else {
            $value = $result;
        }

        return [
            $index,
            $value,
        ];
    }

    /**
     * @param MediaMetadataModel $f
     * @param string      $act
     * @param array[]     $ctl
     */
    private function editFixtureProperty(MediaMetadataModel $f, $act, $ctl)
    {
        if (!array_key_exists($act, $ctl)) {
            $this->io->error('Invalid selection of '.$act);

            return;
        }

        $property = $ctl[$act][0];
        $getMethod = 'get'.ucfirst($property);
        $setMethod = 'set'.ucfirst($property);

        $name = $ctl[$act][1];
        $oldValue = call_user_func([$f, $getMethod]);

        if (is_bool($oldValue)) {
            $oldValue = $oldValue === true ? 'true' : 'false';
        }

        $value = $this->io->ask(sprintf('EDITOR: Enter new value for "%s"', $name), $oldValue);

        if ($property === 'enabled' && strtolower($value) === 'false') {
            $value = false;
        }

        call_user_func([$f, $setMethod], $value);
    }

    /**
     * @param MediaMetadataModel $f
     */
    private function editFixtureSubtitle(MediaMetadataModel $f)
    {
        $this->io
            ->environment(StyleInterface::VERBOSITY_VERBOSE)
            ->comment('Listing fixture subtitles');

        while (true) {
            $subtitles = $this->getEditFixtureSubtitleTable($f);

            if (count($subtitles) == 0) {
                break;
            }

            $this->io->table(...$subtitles);
            $action = trim(strtolower($this->io->ask('Enter value number or no value to exit editor', 'exit')));

            switch ($action) {
                case 'done':
                case 'exit':
                    break 2;

                default:
                    $this->editFixtureSubtitleProperty($f, $action);
            }
        }
    }

    /**
     * @param MediaMetadataModel $f
     *
     * @return array
     */
    private function getEditFixtureSubtitleTable(MediaMetadataModel $f)
    {
        $i = 0;
        $tableRows = array_map(function (SubtitleMetadataModel $subtitle) use ($f, &$i) {
            return $this->getEditFixtureSubtitleTableRow($subtitle, $f, $i);
        }, $f->getSubtitles());

        return array_merge([['[#] (Active)', 'Subtitle', 'Size', 'Language', 'Similarity']], $tableRows);
    }

    /**
     * @param SubtitleMetadataModel $subtitle
     * @param MediaMetadataModel    $media
     * @param int                   $i
     *
     * @return array
     */
    private function getEditFixtureSubtitleTableRow(SubtitleMetadataModel $subtitle, MediaMetadataModel $media, int &$i)
    {
        $active = $media->getActiveSubtitleIndex() === $i;

        return [
            sprintf('[%d] (%s)', $i++, $active ? '*' : '-'),
            $subtitle->getFile()->getBasename(),
            $subtitle->getFileSize(),
            $subtitle->hasLanguage() ? $subtitle->getLanguage() : 'n/a',
            $subtitle->getSimilarity(),
        ];
    }

    /**
     * @param MediaMetadataModel $media
     * @param string             $act
     * @param array[]            $ctl
     */
    private function editFixtureSubtitleProperty(MediaMetadataModel $media, $action)
    {
        $oldSelection = $media->hasActiveSubtitle() ? $media->getActiveSubtitle() : null;

        if (!$media->setActiveSubtitle($action)) {
            $this->io->error(sprintf('Invalid subtitle selection of %s!', $action));

            return;
        }

        $media->getActiveSubtitle()->setEnabled(true);

        if ($oldSelection) {
            $oldSelection->setEnabled(false);
        }
    }

    /**
     * @param MediaMetadataModel $f
     *
     * @return int
     */
    private function remove(MediaMetadataModel $f)
    {
        $relativePathName = $f->getFile()->getRealPath();
        $relativePath = pathinfo($relativePathName, PATHINFO_DIRNAME);
        $absolutePathName = $f->getFile()->getRealPath();
        $absolutePath = pathinfo($absolutePathName, PATHINFO_DIRNAME);

        $removeDirectory = $this->io->confirm('Remove directory path and all its contents?', false);
        $removeItem = $removeDirectory === true ? $absolutePath : $absolutePathName;

        $this->io->warning(
            sprintf(
                'Remove %s %s',
                $removeDirectory === true ? 'directory' : 'file',
                $removeDirectory === true ? $relativePath : $relativePathName
            )
        );

        if ($this->io->confirm('Continue with deletion', true) === false) {
            return 1;
        }

        if (!is_writable($removeItem)) {
            $this->io->error(sprintf('Could not delete "%s"', $relativePathName));

            return 1;
        }

        if ($removeDirectory === true) {
            return $this->removeFilePath($f, $removeItem);
        }

        return $this->removeFileItem($f, $removeItem);
    }

    /**
     * @param MediaMetadataModel $f
     * @param string      $path
     *
     * @return int
     */
    private function removeFileItem(MediaMetadataModel $f, $path)
    {

        $this->io
            ->environment(StyleInterface::VERBOSITY_VERBOSE)
            ->comment(sprintf('Removing "%s"', $path));

        if (false === @unlink($path)) {
            $this->io->error(sprintf('Could not remove "%s"', $path));

            return 1;
        }

        $f->setEnabled(false);

        return 2;
    }

    /**
     * @param MediaMetadataModel $f
     * @param string      $path
     *
     * @return int
     */
    private function removeFilePath(MediaMetadataModel $f, $path)
    {
        $resultSet = [];

        foreach (array_diff(scandir($path), array('..', '.')) as $file) {
            if (is_dir($path.DIRECTORY_SEPARATOR.$file)) {
                $resultSet[] = $this->removeFilePath($f, $path.DIRECTORY_SEPARATOR.$file);
            } else {
                $resultSet[] = $this->removeFileItem($f, $path.DIRECTORY_SEPARATOR.$file);
            }
        }

        $this->io
            ->environment(StyleInterface::VERBOSITY_VERBOSE)
            ->comment(sprintf('Removing "%s"', $path));

        $resultsSet[] = @rmdir($path) === false ? 1 : 2;

        $resultSet = array_filter($resultSet, function ($result) {
            return $result !== 2;
        });

        if (count($resultSet) !== 0) {
            $this->io->error(sprintf('Could not remove "%s"', $path));

            return 1;
        }

        $f->setEnabled(false);

        return 2;
    }

    /**
     * @param MediaMetadataModel     $f
     * @param object|object[] $parameters
     */
    private function hydrateFixture(MediaMetadataModel $f, ...$parameters)
    {
        if ($f instanceof EpisodeMetadataModel) {
            $this->hydrateFixtureEpisode($f, ...$parameters);
        } elseif ($f instanceof MovieMetadataModel) {
            $this->hydrateFixtureMovie($f, ...$parameters);
        }
    }

    /**
     * @param EpisodeMetadataModel $f
     * @param Tv\Episode|null    $e
     * @param Tv|null            $s
     */
    private function hydrateFixtureEpisode(EpisodeMetadataModel $f, Tv\Episode $e = null, Tv $s = null)
    {
        if ($s === null || $e === null) {
            return;
        }

        $f->setName($s->getName());
        $f->setTitle($e->getName());
        $f->setEpisodeNumberStart($e->getEpisodeNumber());
        $f->setSeasonNumber($e->getSeasonNumber());
        $f->setId($e->getId());
        $f->setYear($s->getFirstAirDate()->format('Y'));
        $f->setEnabled(true);
    }

    /**
     * @param MovieMetadataModel $f
     * @param Movie|null       $m
     */
    private function hydrateFixtureMovie(MovieMetadataModel $f, Movie $m = null)
    {
        if ($m === null) {
            return;
        }

        $f->setName($m->getTitle());
        $f->setId($m->getId());
        $f->setYear($m->getReleaseDate()->format('Y'));
        $f->setEnabled(true);
    }

    /**
     * @param ResultCollection $resultSet
     * @param int              $selection
     *
     * @return Tv|Movie
     */
    private function getResultSelection(ResultCollection $resultSet, $selection = 1)
    {
        $keys = $resultSet->getKeys();

        if (--$selection > count($keys)) {
            $selection = 0;
        }

        if (!array_key_exists($selection, $keys)) {
            return null;
        }

        return $resultSet->get($keys[$selection]);
    }

    /**
     * @param MediaMetadataModel $f
     */
    private function writeLookupFailure(MediaMetadataModel $f)
    {
        if ($f instanceof EpisodeMetadataModel) {
            $this->writeLookupFailureEpisode($f);
        } elseif ($f instanceof MovieMetadataModel) {
            $this->writeLookupFailureMovie($f);
        }
    }

    /**
     * @param MediaMetadataModel|EpisodeMetadataModel|MovieMetadataModel $f
     * @param Movie[]|Tv[]|Tv\Episode[]                       ...$parameters
     */
    private function writeLookupSuccess(MediaMetadataModel $f, ...$parameters)
    {
        if (count($parameters) > 1 && $f instanceof EpisodeMetadataModel) {
            $this->writeLookupSuccessEpisode($f, ...$parameters);
        } elseif (count($parameters) > 1 && $f instanceof MovieMetadataModel) {
            $this->writeLookupSuccessMovie($f, ...$parameters);
        }
    }

    /**
     * @param MovieMetadataModel $f
     * @param Movie            $m
     */
    private function writeLookupSuccessMovie(MovieMetadataModel $f, Movie $m)
    {
        try {
            $fileSize = $f->getFile()->getSizeReadable();
        } catch (\RuntimeException $e) {
            $fileSize = 'UNKNOWN';
            $this->io->warning(sprintf('An error occured while retrieving the file size for %s', $f->getFile()->getPathname()));
        }

        $headers = [
            'File Path',
            'Movie Title',
            'Release Date',
            'Size',
            'API Match',
        ];

        $rows = [
            [$f->getFile()->getPathname()],
            [$m->getTitle()],
            [$m->getReleaseDate()->format('Y\-m\-d')],
            [$fileSize],
            [sprintf('<fg=green>OKAY: %d</>', $m->getId())],
        ];

        if ($this->io->isVerbose()) {
            array_unshift($headers, 'Tvdb Id');
            array_unshift($rows, [$m->getId().($m->getImdbId() === null ? '' : '/'.$m->getImdbId())]);
        }

        $this->io->tableVertical($headers, ...$rows);
    }

    /**
     * @param EpisodeMetadataModel $f
     * @param Tv\Episode         $e
     * @param Tv                 $s
     */
    private function writeLookupSuccessEpisode(EpisodeMetadataModel $f, Tv\Episode $e, Tv $s)
    {
        try {
            $fileSize = $f->getFile()->getSizeReadable();
        } catch (\RuntimeException $e) {
            $fileSize = 'UNKNOWN';
            $this->io()->warning(sprintf('An error occured while retrieving the file size for %s', $f->getFile()->getPathname()));
        }

        $country = '';
        $countrySet = $s->getOriginCountry();

        if ($countrySet->count() > 0) {
            $countryKey = $countrySet->getKeys()[0];
            $country = $countrySet->get($countryKey)->getIso31661();
        }

        if ($this->io->isVerbose()) {
            $headers = [
                'Tvdb Id',
                'File Path',
                'Show Name',
                'Season Number',
                'Episode Number',
                'Episode Title',
                'Origin Country',
                'Air Date',
                'Subtitle',
                'Size',
                'API Match',
            ];

            $rows = [
                [$s->getId()],
                [$f->getFile()->getPathname()],
                [$this->getHighlightedMarkup($s->getName())],
                [$this->getStrongMarkup($e->getSeasonNumber())],
                [$this->getStrongMarkup($e->getEpisodeNumber())],
                [$e->getName()],
                [$country],
                [$e->getAirDate()->format('Y\-m\-d')],
                [$this->getMediaSubtitleTableRowMarkup($f)],
                [$fileSize],
                [sprintf('<fg=green>OKAY: %d/%d</>', $s->getId(), $e->getId())],
            ];
        } else {
            $headers = [
                'File Path',
                'Show Name',
                'Season/Episode',
                'Episode Title',
                'Size',
                'API Match',
            ];

            $rows = [
                [$f->getFile()->getPathname()],
                [$this->getHighlightedMarkup($s->getName())],
                [sprintf('%s/%s', $this->getStrongMarkup($e->getSeasonNumber()), $this->getStrongMarkup($e->getEpisodeNumber()))],
                [$e->getName()],
                [$fileSize],
                [sprintf('<fg=green>OKAY: %d/%d</>', $s->getId(), $e->getId())],
            ];
        }

        $this->io->tableVertical($headers, ...$rows);
    }

    /**
     * @param MovieMetadataModel $f
     */
    private function writeLookupFailureMovie(MovieMetadataModel $f)
    {
        try {
            $fileSize = $f->getFile()->getSizeReadable();
        } catch (\RuntimeException $e) {
            $fileSize = 'UNKNOWN';
            $this->io->warning(sprintf('An error occured while retrieving the file size for %s', $f->getFile()->getPathname()));
        }

        $headers = [
            'File Path',
            'Movie Title',
            'Release Date',
            'Size',
            'API Match',
        ];

        $rows = [
            [$f->getFile()->getPathname()],
            [$this->getHighlightedMarkup($f->getName())],
            [$f->getYear()],
            [$fileSize],
            ['<fg=red>FAIL</>'],
        ];

        if ($this->io->isVerbose()) {
            array_unshift($headers, 'Tvdb Id');
            array_unshift($rows, ['']);
        }

        $this->io->tableVertical($headers, ...$rows);
    }

    /**
     * @param EpisodeMetadataModel $f
     */
    private function writeLookupFailureEpisode(EpisodeMetadataModel $f)
    {
        try {
            $fileSize = $f->getFile()->getSizeReadable();
        } catch (\RuntimeException $e) {
            $fileSize = 'UNKNOWN';
        }

        $rows = [
            ['File Path', $f->getFile()->getPathname()],
            ['Show Name', $this->getHighlightedMarkup($f->getName())],
            ['Season', $this->getStrongMarkup($f->getSeasonNumber())],
            ['Episode Number', $this->getStrongMarkup($f->getEpisodeNumberStart())],
            ['Episode Title', $f->getTitle()],
            ['Air Year', $f->getYear()],
            ['Subtitle', $this->getMediaSubtitleTableRowMarkup($f)],
            ['Size', $fileSize],
            ['API Match', '<fg=red>FAIL</>'],
        ];

        $rowsN = [
            ['File Path', $f->getFile()->getPathname()],
            ['Size', $fileSize],
            ['API Match', '<fg=red>FAIL</>'],
        ];

        if ($this->io->isVerbose()) {
            $this->io->table([], ...$rows);
        } else {
            $this->io->table([], ...$rowsN);
        }
    }

    /**
     * @param int $string
     *
     * @return string
     */
    private function getHighlightedMarkup($string): string
    {
        return sprintf('<fg=yellow;options=bold>%s</>', $string);
    }

    /**
     * @param int $string
     *
     * @return string
     */
    private function getStrongMarkup($string): string
    {
        if (is_int($string)) {
            $string = sprintf('%02d', $string);
        }

        return sprintf('<em>%s</>', $string);
    }

    /**
     * @param MediaMetadataModel $media
     *
     * @return string
     */
    private function getMediaSubtitleTableRowMarkup(MediaMetadataModel $media): string
    {
        if (!$media->hasActiveSubtitle()) {
            return 'none';
        }

        $subtitle = $media->getActiveSubtitle();
        $markup = sprintf('<fg=default>%s</>', $subtitle->getFile()->getFilename());

        if ($subtitle->hasLanguage()) {
            $markup .= sprintf(' <fg=default>[lang:%s]</>', $subtitle->getLanguage());
        }

        $markup .= sprintf(' <fg=%s>(%s)</>', $subtitle->isEnabled() ? 'green' : 'red', $subtitle->isEnabled() ? 'enabled' : 'disabled');

        if (count($media->getSubtitles()) > 1) {
            $markup .= sprintf(' %d/%d', $media->getActiveSubtitleIndex()+1, count($media->getSubtitles()));
        }

        return $markup;
    }
}

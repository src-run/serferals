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

use SR\Console\Style\StyleAwareTrait;
use SR\Console\Style\StyleInterface;
use SR\Serferals\Component\Model\MediaMetadataModel;
use SR\Serferals\Component\Model\EpisodeMetadataModel;
use SR\Serferals\Component\Model\MovieMetadataModel;
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
     * @param FileMetadataTask $fileMetadata
     * @param EpisodeResolver       $episodeResolver
     * @param MovieResolver         $movieResolver
     */
    public function __construct(FileMetadataTask $fileMetadata, EpisodeResolver $episodeResolver, MovieResolver $movieResolver)
    {
        $this->fileMetadata = $fileMetadata;
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

        $this->ioVerbose(function (StyleInterface $io) use ($c) {
            if ($c === 0) {
                return;
            }

            $io->subSection('File API Resolutions');
        });

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
        $showFullHelp = false;

        while (true) {
            $this->io()->section(sprintf('%03d of %03d', $i, $count));

            if (!file_exists($f->getFile()->getPathname())) {
                $this->io()->error(sprintf('File no longer exists: %s', $f->getFile()->getPathname()));
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
                $this->io()->caution('Skipping: Option enabled for API lookup failures to be auto-skip.');
                break;
            }

            $this->ioVerbose(function () use ($mode, &$showFullHelp) {
                $this->writeHelp($mode, $showFullHelp);
            });
            
            try {
                if ($f->getFile()->getSize() < 40000000) {
                    $this->io()->warning('File is likely a ancillary file (sample, trailer, etc). Marking for removal!');
                    $actionDefault = 'r';
                } else {
                    $actionDefault = $results->count() == 0 || !$item ? 's' : 'c';
                }
            } catch (\RuntimeException $e) {
                $actionDefault = $results->count() == 0 || !$item ? 's' : 'c';
            }

            $action = $this->io()->ask('Enter action command shortcut name', $actionDefault);

            switch ($action) {
                case 'c':
                    $this->hydrateFixture($f, $item, $this->getResultSelection($results, $lookupSelection));
                    break 2;

                case 'C':
                    $f->setEnabled(true);
                    break 2;

                case 'e':
                    $this->editFixture($f);
                    break;

                case 'l':
                    $lookupSelection = $this->listResults($results);
                    continue;

                case 's':
                    $f->setEnabled(false);
                    $this->io()->comment('Skipping...');
                    break 2;

                case 'r':
                    $f->setEnabled(false);
                    $removeResult = $this->remove($f);
                    $this->io()->newLine();

                    if ($removeResult === 1) {
                        break 1;
                    } else {
                        break 2;
                    }

                case 'm':
                    $mode = ($mode === EpisodeResolver::TYPE ? MovieResolver::TYPE : EpisodeResolver::TYPE);
                    $this->io()->comment(sprintf(
                        'Lookup mode switched to "%s"',
                        $mode
                    ));
                    break;

                case '?':
                    $showFullHelp = true;
                    break;

                case 'h':
                    $showFullHelp = true;
                    break;

                case 'D':
                    $skipRemaining = true;
                    break 2;

                case 'Q':
                    $this->io()->caution('Exiting per user request.');
                    exit;

                default:
                    $this->io()->error(sprintf('Invalid command shortcut "%s"', $action));
                    sleep(1);
            }
        }

        return $f;
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

                if ($m instanceof Tv) {
                    $country = '';
                    $countrySet = $m->getOriginCountry();

                    if ($countrySet->count() > 0) {
                        $countryKey = $countrySet->getKeys()[0];
                        $country = $countrySet->get($countryKey)->getIso31661();
                    }

                    return ['['.++$i.'] '.$m->getId(), $m->getName(), $m->getFirstAirDate()->format('Y\-m\-d'), $country];
                }

                if ($m instanceof Movie) {
                    return ['['.++$i.'] '.$m->getId(), $m->getTitle(), $m->getReleaseDate()->format('Y\-m\-d'), ''];
                }

                return null;
            },
            $resultSet->getAll())
        );

        array_filter($tableRows, function ($row) {
            return $row !== null;
        });

        $this->ioVerbose(
            function (StyleInterface $io) {
                $io->comment('Listing Tvdb lookup search results');
            }
        );

        $this->io()->table($tableRows, ['[#] Tvdb Id', 'Title', 'Release Year', 'Extra']);
        $selection = $this->io()->ask('Enter result item number', 1, null, function ($value) {
            return (int) $value;
        });

        return (int) $selection;
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
     */
    private function editFixture(MediaMetadataModel $f)
    {
        $this->ioVerbose(
            function (StyleInterface $io) use ($f) {
                $io->comment('Listing fixture property values');
            }
        );

        while (true) {
            list($tableHeads, $tableRows, $control) = $this->getEditFixtureTable($f);
            $this->io()->table($tableRows, $tableHeads);
            $action = strtolower($this->io()->ask('Enter value number or no value to exit editor', 'done'));

            switch ($action) {
                case 'done':
                    break 2;

                default:
                    $this->editFixtureProperty($f, $action, $control);
            }
        }
    }

    /**
     * @param MediaMetadataModel $f
     * @param string      $act
     * @param array[]     $ctl
     */
    private function editFixtureProperty(MediaMetadataModel $f, $act, $ctl)
    {
        if (!array_key_exists($act, $ctl)) {
            $this->io()->error('Invalid selection of '.$act);

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

        $value = $this->io()->ask(sprintf('EDITOR: Enter new value for "%s"', $name), $oldValue);

        if ($property === 'enabled' && strtolower($value) === 'false') {
            $value = false;
        }

        call_user_func([$f, $setMethod], $value);
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

        $removeDirectory = $this->io()->confirm('Remove directory path and all its contents?', false);
        $removeItem = $removeDirectory === true ? $absolutePath : $absolutePathName;

        $this->io()->caution(
            sprintf(
                'Remove %s %s',
                $removeDirectory === true ? 'directory' : 'file',
                $removeDirectory === true ? $relativePath : $relativePathName
            )
        );

        if ($this->io()->confirm('Continue with deletion', true) === false) {
            return 1;
        }

        if (!is_writable($removeItem)) {
            $this->io()->error(sprintf('Could not delete "%s"', $relativePathName));

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
        $this->ioVerbose(function (StyleInterface $io) use ($path) {
            $io->comment(sprintf('Removing "%s"', $path));
        });

        if (false === @unlink($path)) {
            $this->io()->error(sprintf('Could not remove "%s"', $path));

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

        $this->ioVerbose(function (StyleInterface $io) use ($path) {
            $io->comment(sprintf('Removing "%s"', $path));
        });

        $resultsSet[] = @rmdir($path) === false ? 1 : 2;

        $resultSet = array_filter($resultSet, function ($result) {
            return $result !== 2;
        });

        if (count($resultSet) !== 0) {
            $this->io()->error(sprintf('Could not remove "%s"', $path));

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
            $this->io()->warning(sprintf('An error occured while retrieving the file size for %s', $f->getFile()->getPathname()));
        }

        $rows = [
            ['Tvdb Id', $m->getId().($m->getImdbId() === null ? '' : '/'.$m->getImdbId())],
            ['File Path', $f->getFile()->getPathname()],
            ['Movie Title', $m->getTitle()],
            ['Release Date', $m->getReleaseDate()->format('Y\-m\-d')],
            ['Size', $fileSize],
            ['API Match', sprintf('<fg=green>OKAY: %d</>', $m->getId())],
        ];

        $this->ioVerbose(
            function (StyleInterface $style) use ($rows) {
                $style->table($rows, []);
            }
        );

        $rows = [
            ['File Path', $f->getFile()->getPathname()],
            ['Movie Title', $m->getTitle()],
            ['Release Date', $m->getReleaseDate()->format('Y\-m\-d')],
            ['Size', $fileSize],
            ['API Match', sprintf('<fg=green>OKAY: %d</>', $m->getId())],
        ];

        $this->ioNotVerbose(
            function (StyleInterface $style) use ($rows) {
                $style->table($rows, []);
            }
        );
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

        $rows = [
            ['Tvdb Id', $s->getId().'/'.$e->getId()],
            ['File Path', $f->getFile()->getPathname()],
            ['Show Name', $s->getName()],
            ['Season', $e->getSeasonNumber()],
            ['Episode Number', $e->getEpisodeNumber()],
            ['Episode Title', $e->getName()],
            ['Origin Country', $country],
            ['Air Date', $e->getAirDate()->format('Y\-m\-d')],
            ['Size', $fileSize],
            ['API Match', sprintf('<fg=green>OKAY: %d/%d</>', $s->getId(), $e->getId())],
        ];

        $this->ioVerbose(
            function (StyleInterface $style) use ($rows) {
                $style->table($rows, []);
            }
        );

        $rows = [
            ['File Path', $f->getFile()->getPathname()],
            ['Show Name', $s->getName()],
            ['Season/Episode', sprintf('%d/%d', $e->getSeasonNumber(), $e->getEpisodeNumber())],
            ['Episode Title', $e->getName()],
            ['Size', $fileSize],
            ['API Match', sprintf('<fg=green>OKAY: %d/%d</>', $s->getId(), $e->getId())],
        ];

        $this->ioNotVerbose(
            function (StyleInterface $style) use ($rows) {
                $style->table($rows, []);
            }
        );
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
        }

        $rows = [
            ['Tvdb Id', ''],
            ['File Path', $f->getFile()->getPathname()],
            ['Movie Title', $f->getName()],
            ['Release Year', $f->getYear()],
            ['Size', $fileSize],
            ['API Match', '<fg=red>FAIL</>'],
        ];

        $this->ioVerbose(
            function (StyleInterface $style) use ($rows) {
                $style->table($rows, []);
            }
        );

        $rows = [
            ['File Path', $f->getFile()->getPathname()],
            ['Size', $fileSize],
            ['API Match', '<fg=red>Failure</>'],
        ];

        $this->ioNotVerbose(
            function (StyleInterface $style) use ($rows) {
                $style->table($rows, []);
            }
        );
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
            ['Show Name', $f->getName()],
            ['Season', $f->getSeasonNumber()],
            ['Episode Number', $f->getEpisodeNumberStart()],
            ['Episode Title', $f->getTitle()],
            ['Air Year', $f->getYear()],
            ['Size', $fileSize],
            ['API Match', '<fg=red>FAIL</>'],
        ];

        $this->ioVerbose(
            function (StyleInterface $style) use ($rows) {
                $style->table($rows, []);
            }
        );

        $rows = [
            ['File Path', $f->getFile()->getPathname()],
            ['Size', $fileSize],
            ['API Match', '<fg=red>FAIL</>'],
        ];

        $this->ioNotVerbose(
            function (StyleInterface $style) use ($rows) {
                $style->table($rows, []);
            }
        );
    }

    /**
     * @param string $mode
     * @param bool   $showFullHelp
     */
    private function writeHelp($mode, &$showFullHelp = false)
    {
        $help = [
            'c' => ['Continue',         'Accept entry details and move to next',                   false],
            'C' => ['Forced Continue',  'Enable manually described entry',                         true],
            's' => ['Skip',             'Ignore/skip over entry and move to next',                 false],
            'm' => ['Mode',             sprintf('Change API lookup mode to "%s"', ucwords(EpisodeResolver::TYPE ? MovieResolver::TYPE : EpisodeResolver::TYPE)), true],
            'e' => ['Edit Fixture',     'Manually edit all entry details',                         true],
            'l' => ['List API Results', 'Show listing of API search results',                      true],
            'r' => ['Remove',           'Remove entry\'s file or path',                            true],
            'D' => ['Done/Write',       'Write out enabled entries and skip remaining',            true],
            'Q' => ['Quit',             'Quit without writing anything',                           true],
        ];

        $maxActionLength = 0;
        foreach ($help as $h) {
            if ($h[2] === true && $showFullHelp !== true) {
                continue;
            }

            if (strlen($h[0]) > $maxActionLength) {
                $maxActionLength = strlen($h[0]);
            }
        }

        $this->ioVerbose(function () {
            $this->io()->comment('Listing available actions');
            $this->io()->newLine();
        });

        foreach ($help as $key => $h) {
            list($action, $description, $full) = $h;

            if ($full === true && $showFullHelp !== true) {
                continue;
            }

            $this->writeHelpLine($key, $action, $description, $showFullHelp, $maxActionLength);
        }

        $this->writeHelpLine('?', 'Help', 'Display listing of all available actions with help text', $showFullHelp, $maxActionLength);

        $showFullHelp = false;
    }

    /**
     * @param string $key
     * @param string $action
     * @param string $description
     * @param bool   $showFullHelp
     * @param int    $padding
     */
    protected function writeHelpLine($key, $action, $description, $showFullHelp, $padding)
    {
        $this->io()->writeln(sprintf(
            ' [ <em>%s</em> ] %s%s<comment>%s</comment>',
            $key,
            $action,
            $showFullHelp ? str_repeat(' ', $padding - strlen($action) + 1) : '',
            $showFullHelp ? strtolower($description) : ''
        ));
    }
}

/* EOF */

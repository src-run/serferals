<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Console\Options\Descriptor;

use SR\Serferals\Component\Console\Options\Runtime\FileOrganizerOptionsRuntime;
use SR\Serferals\Component\Console\Options\Runtime\OptionsRuntime;

class FileOrganizerOptionsDescriptor extends OptionsDescriptor
{
    /**
     * @param OptionsRuntime|FileOrganizerOptionsRuntime $runtime
     *
     * @return mixed[]
     */
    protected function getDescribeTable(OptionsRuntime $runtime): array
    {
        $h = [
            'Output Directory',
            'Skip Lookup Failures',
            'Smart Overwrite',
            'Blind Overwrite',
            'Pre-Clean Exts',
            'Search Media Exts',
            'Search Subtitle Exts',
            'Post-Clean Exts',
            'Media Mode',
            'Placement Mode',
            'Associate Subtitles',
        ];
        $r = [
            $runtime->getOutputPath(),
            $this->getBooleanStateMarkup($runtime->isFailureSkipped()),
            $this->getBooleanStateMarkup($runtime->isOverwriteSmart()),
            $this->getBooleanStateMarkup($runtime->isOverwriteBlind()),
            $this->getArrayListingMarkup($runtime->getCleanFirstExt()),
            $this->getArrayListingMarkup($runtime->getSearchMediaExt()),
            $this->getArrayListingMarkup($runtime->getSearchSubExt()),
            $this->getArrayListingMarkup($runtime->getCleanAfterExt()),
            $this->getStyledMarkup($runtime->isActionModeEpisodes() ? 'episode' : ($runtime->isActionModeMovies() ? 'movie' : $this->getNullStateMarkup('default')), 'yellow', null),
            $this->getStyledMarkup(sprintf('%s (%s)', $runtime->isPlacedModeCopy() ? 'copy' : ($runtime->isPlacedModeMove() ? $this->getStyledMarkup('move', 'yellow', null, 'bold') : $this->getStyledMarkup('default')), $runtime->getPlacedModeType()), 'yellow', null),
            $this->getBooleanStateMarkup(!$runtime->isSubtitleAssociationsDisabled()),
        ];

        foreach ($runtime->getSearchPaths() as $i => $path) {
            array_unshift($h, sprintf('Search Directory (#%d)', $i + 1));
            array_unshift($r, $path);
        }

        return $this->prepareTableForVariadicMethod($h, $r);
    }
}

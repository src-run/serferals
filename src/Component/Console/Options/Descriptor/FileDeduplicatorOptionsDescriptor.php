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

use SR\Serferals\Component\Console\Options\Runtime\FileDeduplicatorOptionsRuntime;
use SR\Serferals\Component\Console\Options\Runtime\OptionsRuntime;

class FileDeduplicatorOptionsDescriptor extends OptionsDescriptor
{
    /**
     * @param OptionsRuntime|FileDeduplicatorOptionsRuntime $runtime
     *
     * @return mixed[]
     */
    protected function getDescribeTable(OptionsRuntime $runtime): array
    {
        $h = [
            'Ignored Extension List',
            'Ignored Regexps List',
            'Maximum File Size',
            'Dry Run Operations Mode',
        ];

        $r = [
            $this->getArrayListingMarkup($runtime->getIgnoredFiles()),
            $this->getArrayListingMarkup($runtime->getIgnoredPaths()),
            $runtime->hasMaxFileSize() ? $runtime->getMaxFileSizeFormatted() : $this->getNullStateMarkup(),
            $this->getBooleanStateMarkup($runtime->isModeDryRun()),
        ];

        foreach ($runtime->getSearchPaths() as $index => $path) {
            array_unshift($h, sprintf('Search Directory %03d', $index + 1));
            array_unshift($r, sprintf('<fg=white;options=bold>%s</>', $path));
        }

        return $this->prepareTableForVariadicMethod($h, $r);
    }
}

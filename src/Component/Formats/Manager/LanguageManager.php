<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Formats\Manager;

use SR\Serferals\Component\Formats\Configuration\LanguageConfiguration;

class LanguageManager extends AbstractManager
{
    /**
     * @return LanguageConfiguration
     */
    public function getISO639Language(): LanguageConfiguration
    {
        return $this->getConfiguration()->getISO6391();
    }
}

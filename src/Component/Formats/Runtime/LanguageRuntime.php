<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Formats\Runtime;

use SR\Serferals\Component\Formats\Configuration\LanguageConfiguration;

class LanguageRuntime extends AbstractRuntime
{
    /**
     * @var LanguageConfiguration[]
     */
    private $iso6391;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->iso6391 = new LanguageConfiguration($data['iso639'], 'LanguageISO639');
    }

    /**
     * @return LanguageConfiguration
     */
    public function getISO6391(): LanguageConfiguration
    {
        return $this->iso6391;
    }
}

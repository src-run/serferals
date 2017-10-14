<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Console\Helper;

use SR\Serferals\Component\Console\Helper\Action\ActionModel;

class MetadataActionHelper extends ActionHelper
{
    /**
     * @return void
     */
    public function setupCollection(): void
    {
        $this->collection->add(new ActionModel('c', 'Continue', 'Accept entry details and move to next', false));
        $this->collection->add(new ActionModel('C', 'Forced Continue', 'Enable manually described entry', true));
        $this->collection->add(new ActionModel('s', 'Skip', 'Ignore/skip over entry and move to next', false));
        $this->collection->add(new ActionModel('m', 'Mode', 'Change API lookup mode', true));
        $this->collection->add(new ActionModel('e', 'Edit Fixture', 'Manually edit all entry details', true));
        $this->collection->add(new ActionModel('l', 'List API Results', 'Show listing of API search results', true));
        $this->collection->add(new ActionModel('t', 'Toggle Subtitle', 'Enables/disable media subtitle entry', true));
        $this->collection->add(new ActionModel('T', 'Select Subtitle', 'Display available subtitles and allow selection', true));
        $this->collection->add(new ActionModel('r', 'Remove', 'Remove file or path', true));
        $this->collection->add(new ActionModel('D', 'Done/Write', 'Write out enabled entries and skip remaining', true));
    }
}

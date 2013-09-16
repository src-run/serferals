# Serfer (Version 0.1.0)

* Website: http://robfrawley.com/projects/serfer
* Written by: Rob Frawley, projects@robfrawley.com
* Copyright: 2009 Inserrat LLC, http://inserrat.com
* Licensed under: MIT License (See ./COPYING)

## Overview
Series/Episode Filename Renamer and Lookup Script

Uses epguides.com to look up the air date and title of every episode of the 
specified series, then looks through the local collection of files, searching for Sxx and Exx in filenames, where xx stands for two digits, for example S01E04 for Season 01 Episode 04. For files where this information is found, data is used from epguides for that specific episode to name it based on the filename skeleton defined. Ones that cannot be done so automatically can be skipped or the information for each can be manually entered by the user from epguides. At this point the script has not actually *written* any changed. Finally, it will ask for you to confirm your actions, and it will attempt to run through all logged actions, renaming the files as specified.

CONFIGURATION

By default, the program is NOT i debug mode, and therefore only displays semi-verbose output. The default file renaming skeleton is as follows: `${seriesname} S{episodeseason}E${episodenumber} ${airdate} ${episodename}`
which is a simple and clean naming convention. For example, for Season 02, Episode 02 of Fringe, the filename would look as follows: `Fringe S02E02 2009-24-09 Night of Desirable Objects.avi`
More needs to be written here (See TODO)...

$$ Todo

* Finish this README file...

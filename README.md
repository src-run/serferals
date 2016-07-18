# [src-run] serferals

## Overview

[Welcome](https://src.run/go/readme_welcome)!
The `src-run/serferals` package provides a CLI application for looking-up and organizing media files, with support for movies and tv shows.

### JTT

This package represents a single project within a
[large collection](https://src.run/go/explore) of open-source code released
under the *SR* namespace, comprised of framework-agnostic libraries,
and a number of Symfony bundles. These projects are authored and maintained
by [Rob Frawley 2nd](https://src.run/rmf) and 
[collaborators](https://src.run/serferals/github_collaborators).

## Demo

[![Serferals basic usage example](https://src.run/get/images/serferals-console-help-video.png)](https://www.youtube.com/watch?v=8S1q_pZVDgs)

## Quick Start

### Installation

For API lookups to operate correctly, you must first request a free API key from [The Movie DB](https://www.themoviedb.org/). This API key must be entered into the `parameters.yml` config file.

To install, clone the repository, copy and edit the `parameters.yml` file, and use the `make` script to build the executable (which runs composer to get the latest dependencies, grabs [Box](https://github.com/box-project/box2), and uses it to compile the Symfony console app into a single-file PHAR executable).

```bash
git clone https://github.com/robfrawley/serferals.git
cp app/config/parameters.yml.dist app/config/parameters.yml
nano app/config/parameters.yml
./make
```

**Note:** If the build fails, you likely need to edit your php.ini file to enable PHAR creation by changing `phar.readonly = On` to `phar.readonly = Off`.

## Reference

My prefered CLI usage include the `-vvv` and `-s` options to enable verbose output and smart overwrite

```bash
serferals -vvv -s -o /path/to/output /paths/to/scan [...]
```

## Contributing

### Discussion

For general inquiries or to discuss a broad topic or idea, you can find
*robfrawley* on Freenode. There is also a *#scribe* channel, which can
be joined using the following link
[irc.choopa.net:6669/scribe](irc://irc.choopa.net:6669/scribe).

### Issues

To report issues or request a new feature use
[GitHub](https://src.run/serferals/github_issues)
or [GitLab](https://src.run/serferals/gitlab_issues)
to start a discussion. Include as much information as possible to aid in
a quick resolution. Feel free to "ping" the topic if you don't get a
response within a few days.

### Code

You created additional functionality during the use of this package? Send
it back upstream! *Don't hesitate to submit a pull request!* Beyond the
brief requirements outlined in the
[contibuting guide](https://src.run/serferals/contributing),
your [imagination](https://src.run/go/readme_imagination)
represents the only limitation.

## License

This project is licensed under the
[MIT License](https://src.run/go/mit), an
[FSF](https://src.run/go/fsf)-/[OSI](https://src.run/go/osi)-approved
and [GPL](https://src.run/go/gpl)-compatible, permissive free software
license. Review the
[LICENSE](https://src.run/serferals/license)
file distributed with this source code for additional information.

## Additional Links

|       Purpose | Status        |
|--------------:|:--------------|
| *Stable Release*    | [![Packagist](https://src.run/serferals/packagist_shield)](https://src.run/serferals/packagist) |
| *Dev Release*    | [![Packagist](https://src.run/serferals/packagist_pre_shield)](https://src.run/serferals/packagist) |
| *License*    | [![License](https://src.run/serferals/license_shield)](https://src.run/serferals/license) |

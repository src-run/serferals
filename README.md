# [src-run] serferals


[Welcome](https://src.run/go/readme_welcome)!
The `src-run/serferals` package provides a CLI application for looking-up and
organizing media files, with support for movies and tv shows.


## JTT

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

Before beginning, ensure you have created an account and requested a free API key 
from [The Movie DB](https://www.themoviedb.org/) website. Once you have an API key,
take note of it and enter it when prompted by the `make` script.

> **Note**: For the installation to complete successfully, **PHAR archive writing 
> must not be disabled**. To find the location of your configuration file, run
> `php -i | grep "Loaded Configuration File"`. Edit your `php.ini` file, ensuring 
> the variable `phar.readonly` is uncommented and assigned the value `Off`.

```bash
git clone https://github.com/robfrawley/serferals.git && cd serferals
./make
```

If installation completes without error, the final line of output will be the 
version string of the serferals command.

```txt
src-run/serferals version 2.1.3 by Rob Frawley 2nd <rmf@src.run> (69975c3)
```

## Reference

My prefered CLI usage includes the `-vvv` and `-s` options, enabling verbose output 
and the "smart overwrite" feature.

```bash
serferals -vvv -s -o /output/path /input/path/foo [...] /input/path/bar
```

The only required option is the output path (`-o|--output-path`). At least one 
input path must be provided as an argument, though you can specify multiple input 
paths if required.

```bash
serferals --output-path=/output/path /input/path [...]
```

## Contributing

### Discussion

For general inquiries or to discuss a broad topic or idea, find "robfrawley" on Freenode. He is always happy to 
discuss language-level ideas, possible new directions for a project, emerging technologies, as well as the weather.

### Issues

To report issues or request a new feature, use the [project issue tracker](https://src.run/serferals/github_issues).
Include as much information as possible in any bug reports. Feel free to "ping" the topic if you don't get a response
within a few days (sometimes Github notification e-mails fall through the cracks).

### Code

You created additional functionality while utilizing this package? Wonderful: send it back upstream! *Don't hesitate to
submit a pull request!* Your [imagination](https://src.run/go/readme_imagination) and the requirements outlined within
our [CONTRIBUTING.md](https://src.run/serferals/contributing) file are the only limitations.


## License

This project is licensed under the [MIT License](https://src.run/go/mit), an [FSF](https://src.run/go/fsf)- and 
[OSI](https://src.run/go/osi)-approved, [GPL](https://src.run/go/gpl)-compatible, permissive free software license.
Review the [LICENSE](https://src.run/serferals/license) file distributed with this source code for additional
information.


## Additional Links

| Item               | Result/Status                                                                                                      |
|-------------------:|:-------------------------------------------------------------------------------------------------------------------|
| __Stable Release__ | [![Packagist](https://src.run/serferals/packagist.svg)](https://src.run/serferals/packagist)     |
| __Dev Release__    | [![Packagist](https://src.run/serferals/packagist_pre.svg)](https://src.run/serferals/packagist) |
| __License__        | [![License](https://src.run/serferals/license.svg)](https://src.run/serferals/license)           |
| __Reference__      | [![License](https://src.run/serferals/api.svg)](https://src.run/serferals/api)                   |

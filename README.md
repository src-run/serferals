
# [src-run] serferals


[Welcome](https://src.run/go/readme_welcome)! The `src-run/serferals` package provides a CLI application for looking-up
and organizing media files, with support for movies and TV episodes.

For example, take an input folder containing the following set of files.

```txt
1. Stranger Things S01E01.mkv
2. stranger_things_201e02_hdtv.mkv
3. 3:10-To-Yuma-720p-2007.mkv
```

After scanning, parsing, and performing lookups against the above files, Serferals will move them to the following file
paths.

```txt
1. tv/Stranger Things (2016)/Season 01/Stranger Things (2016) [S01E01] Chapter One: The Vanishing Of Will Byers.mkv
2. tv/Stranger Things (2016)/Season 01/Stranger Things (2016) [S01E02] Chapter Two: The Weirdo on Maple Street.mkv
3. movie/3:10 to Yuma (2007)/3:10 to Yuma (2007) [5176].mkv
```

This behavior is especially useful for media servers such as [Plex](https://www.plex.tv/downloads/) that require files
follow specific naming conventions. It can also be useful for those with OCD who simply require their files are named
consistently for archival.

### Customization

The output paths can be configured easily by adding custom templates to the `parameters.yml` configuration file. For
example, the default templates output file paths unsupported by Windows file systems (NTFS) due to character restrictions,
requiring custom configuration on non-Linux and non-OSX operating systems.

The templates for the output paths are easy to edit as they use [Twig](http://twig.sensiolabs.org/) syntax. For example,
the following is the template used for TV episodes.

```twig
tv/{{ name|raw }}{% if year is defined %} ({{ year }}){% endif %}/Season {{ season }}/{{ name|raw }}{% if year is defined %} ({{ year }}){% endif %} [S{{ season }}E{{ start }}{% if end is defined %}-{{ end }}{% endif %}]{% if title is defined %} {{ title|raw }}{% endif %}.{{ ext }}
```

The above template may at first appear complex, but breaking it down shows the true simplicity.

```twig
tv/                           # tv/
  {{ name|raw }}              # Stranger Things
  {% if year is defined %}    # <boolean:true>
    ({{ year }})              # (2016)
  {% endif %}
  /Season {{ season }}/       # Season 01/
  {{ name|raw }}              # Stranger Things
  {% if year is defined %}    # <boolean:true>
    ({{ year }})              # (2016)
  {% endif %}
  [                           # [
    S{{ season }}             # S01
    E{{ start }}              # E01
    {% if end is defined %}   # <boolean:false>
      -{{ end }}
    {% endif %}
  ]                           # ]
  {% if title is defined %}   # <boolean:true>
    {{ title|raw }}           # Chapter One: The Vanishing Of Will Byers
  {% endif %}
  .{{ ext }}                  # .mkv
```

This shows how the script arrives at the final file path for season 1, episode 1 of Netflix's new series
[Stranger Things](https://en.wikipedia.org/wiki/Stranger_Things_(TV_series)).

```txt
tv/Stranger Things (2016)/Season 01/Stranger Things (2016) [S01E01] Chapter One: The Vanishing Of Will Byers.mkv
```

## JTT

This package represents a single project within a [large collection](https://src.run/go/explore) of open-source code
released under the *SR* namespace, comprised of framework-agnostic libraries, and a number of Symfony bundles. These
projects are authored and maintained by [Rob Frawley 2nd](https://src.run/rmf) and 
[collaborators](https://src.run/serferals/github_collaborators).


## Quick Start


### Basic Usage Video

[![Serferals basic usage example](https://src.run/get/images/serferals-thumb-usage.png)](https://src.run/go/serferals-video-usage)


### Installation

Before beginning, ensure you have created an account and requested a free API key from
[The Movie DB](https://www.themoviedb.org/) website. Once you have an API key, take note of it and enter it when
prompted by the `make` script.

> **Note**: For the installation to complete successfully, **PHAR archive writing must not be disabled**. To find the
> location of your configuration file, run `php -i | grep "Loaded Configuration File"`. Edit your `php.ini` file,
> ensuring the variable `phar.readonly` is uncommented and assigned the value `Off`.

```bash
git clone https://github.com/robfrawley/serferals.git && cd serferals
./make
```

If installation completes without error, the final line of output will be the version string of the serferals command.

```txt
src-run/serferals version 2.2.3 by Rob Frawley 2nd <rmf@src.run> (69975c3)
```

### Troubleshooting

If you experience issues with the installer script, debug mode can be enabled by defining a bash variable when calling
`make`.

```bash
SERFERALS_DEBUG=true ./make
```

Additionally, you can enable "clean installation" mode, which ensures all dependencies and helper PHARs (Composer, Box)
are forcefully re-fetched.

```bash
SERFERALS_CLEAN=true ./make
```

Moreover, you can enable "pristine installation" mode, which forces removal and re-creation of configuration files as
well as enables everything from "clean installation" mode.

```bash
SERFERALS_PRISTINE=true ./make
```

Lastly, all the above mentioned environment variables can be passed in any combination.

```bash
SERFERALS_DEBUG=true SERFERALS_PRISTINE=true ./make
```

> **Note:** All troubleshooting variables are only checked to see if they are defined or undefined; they are not checked
for a specific value. **Their value is irrelevant.** Calling `SERFERALS_DEBUG=false ./make` will enable "debug mode"
because the variable is defined.


#### Installation Video

[![Serferals installation video](https://src.run/get/images/serferals-thumb-install.png)](https://src.run/go/serferals-video-install)

## Reference

My prefered CLI usage includes the `-vvv` and `-s` options, enabling verbose output and the "smart overwrite" feature.

```bash
serferals -vvv -s -o /output/path /input/path/foo [...] /input/path/bar
```

The only required option is the output path (`-o|--output-path`). At least one input path must be provided as an argument,
though you can specify multiple input 
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


## API Usage

[![PThe Movie Database](https://src.run/get/images/tmdb-logo-91x81.png)](https://src.run/serferals/packagist)

Serferals episode and movie lookup powered by
[The Movie Database](https://www.themoviedb.org/)
[API](http://docs.themoviedb.apiary.io/).


# Contributing

Looking to contribute a pull-request back to a **Source Consulting**
project? Great! Before sending a pull-request, ensure the following
requirements (__A__, __B__, and __C__) described below are properly met.
This will ensure your contribution is merged smoothly and quickly.

## A. Code Style

All projects in the `SR` namespace adhere to strict code-style
requirements. The expected style is guaranteed through use of the
excellent auto-code-styling project
[PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer).

### Download Fixer

__A.__ To download *PHP CS Fixer* using *curl*:

```bash
curl http://get.sensiolabs.org/php-cs-fixer.phar -o php-cs-fixer
```

__B.__ If *curl* is unavailable, download using *wget*:

```bash
wget http://get.sensiolabs.org/php-cs-fixer.phar -O php-cs-fixer
```

### Install Fixer

It is recommended to install *PHP-CS-Fixer* at a system-level, making it
available within your normal `PATH` environment variable (allowing you
to call `php-cs-fixer` from any location). After downloading:

```bash
sudo chmod a+x php-cs-fixer
sudo mv php-cs-fixer /usr/local/bin/php-cs-fixer
```

### Run Fixer

When running *PHP CS Fixer*, there are a collection of "rules" that can
be passed via the --rules command-line option. Rules are also grouped
into "rule collections" that are prefixed by an "@" (at symbol). To
disable a rule, it should be prefixed by an "-" (minus sign).

*PHP CS Fixer* must be configured and run with the following rules:
- `@Symfony`
- `-simplified_null_return`

For example, to fix code within the `lib` directory of the current path:

```bash
php-cs-fixer fix lib/ --rules=@Symfony,-simplified_null_return
```

## B. New Files

Every file must contain a file-level "doc-block" following the below
template. The placeholder `PACKAGE_NAME` must match the
[Packagist](https://packagist.org/) project name for the respective file.

```php
/*
 * This file is part of the `PACKAGE_NAME` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */
```

An "end of file" comment followed by an empty new line, must be present
at the end of all files.

```php
/* EOF */

```

## C. Attribution

Sometimes completely new classes are written by a contributor. When
this is the case, *the contributor __may__ choose (at their discretion)*,
to use a class-level "doc-block" to provide attribution using the
following template.

```php
/**
 * Class ReallyCoolContributedClass.
 *
 * @author CONTRIBUTOR_NAME <CONTRIBUTOR_EMAIL>
 */
class ReallyCoolContributedClass
{
    // ...
}
```

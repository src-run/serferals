#!/usr/bin/env php
<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

if (defined('HHVM_VERSION_ID')) {
    fwrite(STDERR, "HHVM not surrently supported!\n");
    exit(1);
} elseif (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50600) {
    fwrite(STDERR, "PHP needs to be a minimum version of PHP 5.6.0\n");
    exit(1);
}

set_error_handler(function ($severity, $message, $file, $line) {
    if ($severity & error_reporting()) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

Phar::mapPhar('serferals.phar');

require_once 'phar://serferals.phar/vendor/autoload.php';

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

$container = new ContainerBuilder();
$loader = new YamlFileLoader($container, new FileLocator('phar://serferals.phar/app/config'));
$loader->load('services.yml');

$input = $container->get('rmf.serferals.console_input');
$output = $container->get('rmf.serferals.console_output');

$application = $container->get('rmf.serferals.application');
$application->run($input, $output);

__HALT_COMPILER();

/* EOF */

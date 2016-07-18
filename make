#!/bin/bash

set -e

function out() {
	echo -en ">>> [serferals:make $(date +%s.%N)] $1\n"
}

out "Initializing"
rm -fr box.phar
rm -fr composer.phar

out "Installing \"composer\""
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

out "Executing \"composer update\""
php composer.phar update --no-dev

out "Installing \"box\""
curl -LSs https://box-project.github.io/box2/installer.php | php

out "Executing \"box build\""
php box.phar build

out "Installing \"serferals\" bin to \"/usr/local/bin\""
sudo mv serferals.phar /usr/local/bin/serferals
sudo chmod +x /usr/local/bin/serferals

out "Cleaning up"
rm -fr box.phar
rm -fr composer.phar

out "Done!"

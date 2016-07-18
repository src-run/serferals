#!/bin/bash

rm -fr box.phar
rm -fr composer.phar

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

php composer.phar install --no-dev || php composer.phar update --no-dev

curl -LSs https://box-project.github.io/box2/installer.php | php

php box.phar build

sudo mv serferals.phar /usr/local/bin/serferals
sudo chmod +x /usr/local/bin/serferals

rm -fr box.phar
rm -fr composer.phar

#!/bin/bash

set -e

function out_prompt() {
  echo -en "[serferals:make $(date +%s)]"
}

function out() {
  echo -en "--- $(out_prompt) $1\n"
}

out "Checking configuration"

if [[ ! -f "app/config/parameters.yml" ]]; then
  out "Creating \"app/config/parameters.yml\""
  cp app/config/parameters.yml.dist app/config/parameters.yml

  echo -en "\n"
  echo -en ">>> [CONFIG] You must create an account and request a free API key from The Movie Database\n"
  echo -en ">>> [CONFIG] The Movie Database website can be found at https://www.themoviedb.org/\n\n"
  read -p  ">>> [CONFIG] TMDB API Key: " tmdbapi
  sed -i -- "s/tmdb-api-key/$tmdbapi/g" app/config/parameters.yml
  echo -en "\n"
fi

if [[ ! -f composer.phar ]]; then
  out "Fetching composer"
  wget -q https://getcomposer.org/installer -O composer-setup
  php composer-setup > /dev/null
  rm composer-setup
else
  out "Updating composer"
  php composer.phar self-update &> /dev/null
fi

if [[ ! -d vendor ]]; then
  out "Fetching dependencies"
  php composer.phar install --no-dev -q || php composer.phar update --no-dev -q
else
  out "Updating dependencies"
  php composer.phar update --no-dev -q
fi

if [[ -f box.phar ]]; then
  rm box.phar
fi

out "Fetching box"
wget https://box-project.github.io/box2/installer.php -O box-setup -q
php box-setup > /dev/null
rm box-setup

out "Building executable (this could take some time)"
php box.phar build > /dev/null

chmod a+x serferals.phar
sudo -p "!!! $(out_prompt) Password required to place executable (sudo): " mv serferals.phar /usr/local/bin/serferals

if [[ ! $(which serferals) ]]; then
  out "Failure!"
  exit -1
fi

out "Finished installation"

echo -en "\n"
echo -en "Installation:\n"
echo -en "\t/usr/local/bin/serferals\n"
echo -en "Usage:\n"
echo -en "\tserferals --help\n"
echo -en "Version:\n"
echo -en "\t"; serferals --version
echo -en "\n"

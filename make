#!/bin/bash

##
 # This file is part of the `src-run/serferals` project.
 #
 # (c) Rob Frawley 2nd <rmf@src.run>
 #
 # For the full copyright and license information, please view the LICENSE.md
 # file that was distributed with this source code.
 ##

set -e

#
# Configuration
#

SET_INSTALL_MODE_CLEAN=0
SET_INSTALL_MODE_PRESTINE=0

YML_CONFIG="app/config/parameters.yml"

DIR_INSTALL="/usr/local/bin"
DIR_SELF="$(dirname $(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/$(basename "${BASH_SOURCE[0]}"))"

BIN_COMPOSER="${DIR_SELF}/composer.phar"
BIN_BOX="${DIR_SELF}/box.phar"
BIN_SERFERALS="${DIR_INSTALL}/serferals"
BIN_SERFERALS_PHAR="${DIR_SELF}/serferals.phar"

GET_COMPOSER="${DIR_SELF}/composer.setup"
GET_BOX="${DIR_SELF}/box.setup"

#
# Function definitions
#

function out_prompt() {
  echo -en "[serferals:make $(date +%s)]"
}

function out() {
  echo -en "--- $(out_prompt) $1\n"
}

#
# ACTION: Output script info
#

echo -en "\n"
echo -en ">>> [INFO] Serferals Installer v1.0.0\n"
echo -en "\n"

#
# ACTION: Require prestine or clean install if requested
#

if [[ ${SET_INSTALL_MODE_PRESTINE} -eq 1 ]]; then
  out "Ensuring prestine installation"

  for f in ${YML_CONFIG} ${GET_COMPOSER} ${GET_BOX} ${BIN_COMPOSER} ${BIN_BOX} vendor; do
    if [[ -f "$f" ]]; then rm "$f";     fi
    if [[ -d "$f" ]]; then rm -fr "$f"; fi
  done

  if [[ -f "$BIN_SERFERALS" ]]; then
    rm "${BIN_SERFERALS}" &>/dev/null || \
      sudo -p "!!! $(out_prompt) Password required to remove old executable (sudo): " rm "${BIN_SERFERALS}"
  fi
fi

if [[ ${SET_INSTALL_MODE_CLEAN} -eq 1 ]]; then
  out "Ensuring clean installation"

  for f in ${GET_COMPOSER} ${GET_BOX} ${BIN_COMPOSER} ${BIN_BOX} vendor; do
    if [[ -f "$f" ]]; then rm "$f";     fi
    if [[ -d "$f" ]]; then rm -fr "$f"; fi
  done
fi

#
# ACTION: Update source
#

out "Updating source"
git pull -q &> /dev/null || out "Updating git failed (are you in a detached head or similar state?)"

#
# ACTION: Add configuration file
#

out "Checking configuration"

if [[ ! -f "${YML_CONFIG}" ]]; then
  out "Creating \"${YML_CONFIG}\""
  cp ${YML_CONFIG}.dist ${YML_CONFIG}

  echo -en "\n"
  echo -en ">>> [CONFIG] You must create an account and request a free API key from The Movie Database\n"
  echo -en ">>> [CONFIG] The Movie Database website can be found at https://www.themoviedb.org/\n\n"
  read -p  ">>> [CONFIG] TMDB API Key: " tmdbapi
  sed -i -- "s/tmdb-api-key/$tmdbapi/g" ${YML_CONFIG}
  echo -en "\n"
fi

#
# ACTION: Download or update composer
#

if [[ ! -f ${BIN_COMPOSER} ]]; then
  out "Fetching composer"
  wget -q https://getcomposer.org/installer -O ${GET_COMPOSER}
  php ${GET_COMPOSER} > /dev/null
  rm ${GET_COMPOSER}
else
  out "Updating composer"
  php ${BIN_COMPOSER} self-update &> /dev/null
fi

#
# ACTION: Install or update dependencies
#

if [[ ! -d vendor ]]; then
  out "Fetching dependencies"
  php ${BIN_COMPOSER} install --no-dev -q || php ${BIN_COMPOSER} update --no-dev -q
else
  out "Updating dependencies"
  php ${BIN_COMPOSER} update --no-dev -q
fi

#
# ACTION: Download box
#

if [[ -f ${BIN_BOX} ]]; then
  rm ${BIN_BOX}
fi

out "Fetching box"
wget https://box-project.github.io/box2/installer.php -O ${GET_BOX} -q
php ${GET_BOX} > /dev/null
rm ${GET_BOX}

#
# ACTION: Run box (create PHAR executable)
#

out "Building executable"
php ${BIN_BOX} build > /dev/null

#
# ACTION: Ready executable and install
#

if [[ ! -f ${BIN_SERFERALS_PHAR} ]]; then
  out "Failure!"
  exit -1
fi

chmod a+x ${BIN_SERFERALS_PHAR}

mv ${BIN_SERFERALS_PHAR} "${BIN_SERFERALS}" &>/dev/null || \
  sudo -p "!!! $(out_prompt) Password required to place executable (sudo): " mv ${BIN_SERFERALS_PHAR} "${BIN_SERFERALS}"

#
# ACTION: Check for serferals in PATH
#

if [[ ! $(which serferals) ]]; then
  out "Failure!"
  exit -1
else
  out "Finished installation"
fi

#
# ACTION: Output installation info
#

echo -en "\n"
echo -en ">>> [INFO] Install Path : ${BIN_SERFERALS}\n"
echo -en ">>> [INFO] Help Command : serferals --help\n"
echo -en ">>> [INFO] CLI Version  : "
serferals --version
echo -en "\n"

# EOF

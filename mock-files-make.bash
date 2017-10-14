#!/bin/bash

source "mock-files.inc.bash"

IFS=$'\n'
for file in $(cat "${LIST_MOCKS}"); do
  LIST_FILES+=("${file}")
done
IFS=${IFS_GO_ORI}

mock_place_files

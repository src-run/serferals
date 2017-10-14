#!/bin/bash

ROOT_WRITE="${1:-mock-media-root/}"
MOCK_COUNT=${2:-20}

LIST_MOCKS="mock-files.list"
LIST_FILES=()
IFS_GO_ORI=${IFS}

if [ "${ROOT_WRITE:0:-1}/" != "${ROOT_WRITE}" ]; then
  ROOT_WRITE="${ROOT_WRITE}/"
fi

if [ "${ROOT_WRITE:0:1}" != "/" ] && [ "${ROOT_WRITE:0:2}" != "./" ]; then
  ROOT_WRITE="./${ROOT_WRITE}"
fi

function write_out_subtitle()
{
  local file="${1}"
  local path="$(dirname "${file}")"

  if [ ! -f "${file}" ]; then
    if [ ! -d "${path}" ]; then
      mkdir -p "${path}" && \
        echo "Created directory: ${path}" || \
        echo "[ERROR] Could not create directory: ${path}"
    fi

    touch "${file}" && \
      echo "Created mock file: ${file} (SUBTITLE)" || \
      echo "[ERROR] Could not create mock file: ${file}"
  fi
}

function write_out_media()
{
  local file="${1}"
  local path="$(dirname "${file}")"

  if [ ! -d "${path}" ]; then
    mkdir -p "${path}" && \
      echo "Created directory: ${path}" || \
      echo "[ERROR] Could not create directory: ${path}"
  fi

  if [ ! -f "${file}" ]; then
    touch "${file}" && \
      echo "Created mock file: ${file} (MEDIA)" || \
      echo "[ERROR] Could not create mock file: ${file}"
  fi
}

function mock_place_files() {
  for mockFile in "${LIST_FILES[@]}"; do
    mockFilePath="${ROOT_WRITE}$(dirname "${mockFile}")"
    mockFileName="$(basename "${mockFile}")"
    mockBaseName="${mockFileName%.*}"
    vidRealPath="${mockFilePath}/${mockFileName}"
    randInteger=${RANDOM}

    write_out_media "${vidRealPath}"

    if [[ $(((${randInteger} % 2))) -eq 0 ]]; then
      subFileExt="srt"
    else
      subFileExt="sub"
    fi

    randRemainder=$(((${randInteger} % 30)))
    if [[ ${randRemainder} -eq 10 ]]; then
      write_out_subtitle "${mockFilePath}/subs/${mockBaseName}.${subFileExt}"
    elif [[ ${randRemainder} -eq 15 ]]; then
      write_out_subtitle "${mockFilePath}/subs/${mockBaseName}.en.${subFileExt}"
    elif [[ ${randRemainder} -eq 20 ]]; then
      write_out_subtitle "${mockFilePath}/subtitles/eng/${mockBaseName}.${subFileExt}"
    elif [[ ${randRemainder} -eq 25 ]]; then
      write_out_subtitle "${mockFilePath}/subs/${mockBaseName}.eng.${subFileExt}"
      write_out_subtitle "${mockFilePath}/subs/${mockBaseName}.ita.${subFileExt}"
      write_out_subtitle "${mockFilePath}/subs/${mockBaseName}.rus.${subFileExt}"
      write_out_subtitle "${mockFilePath}/subs/${mockBaseName}.spa.${subFileExt}"
    elif [[ ${randRemainder} -eq 29 ]]; then
      echo "Skipping subtitle creation for ${file}"
    else
      write_out_subtitle "${mockFilePath}/${mockBaseName}.${subFileExt}"
    fi
  done
}

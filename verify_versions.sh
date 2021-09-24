#!/usr/bin/env bash

### Start of boilerplate

set -euo pipefail
[ -n "${DEBUG:-}" ] && set -x
IFS=$'\n\t'

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" &> /dev/null && pwd)"

log () {
  local log_type
  log_type="$(echo "$1" |tr '[:lower:]' '[:upper:]')"
  shift
  [[ -n "$log_type" ]] && log_type="${log_type}: "
  for message in "$@"; do
    echo "# ${log_type}${message}" >&2
  done
}

### End of boilerplate

assertContains () {
  local -r relativeFilename="${1}"
  local -r pattern="${2}"

  if grep -q "${pattern}" "${DIR}/${relativeFilename}"; then
    log info "✅ ${relativeFilename} contains expected pattern '${pattern}'"
  else
    log fatal "❌ ${relativeFilename} did not contain expected pattern '${pattern}'"
    exit 1
  fi
}

main () {
  local version
  version="$(grep -Po '"version":\s*"\K[^"]*(?=")' "${DIR}/composer.json")"
  log info "Detected version v${version} from composer file"

  assertContains etc/module.xml '<module name="SolveData_Events" setup_version="'"${version}"'">'
  assertContains README.md 'latest version (`v'"${version}"'`)'
  assertContains README.md "composer require solvedata/plugins-magento2==${version}"
}

if [[ "${BASH_SOURCE[0]}" = "$0" ]]; then
  main "$@"
fi

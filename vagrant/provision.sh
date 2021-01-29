#!/usr/bin/env bash

set -euo pipefail
[ -n "${DEBUG:-}" ] && set -x
IFS=$'\n\t'

docker_compose_version='1.28.0'

root_setup () {  
  # Install docker
  curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
  sh /tmp/get-docker.sh
  rm /tmp/get-docker.sh

  systemctl start docker
  systemctl enable docker
  usermod -aG docker vagrant

  # Install docker-compose
  curl -L "https://github.com/docker/compose/releases/download/${docker_compose_version}/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
  chmod +x /usr/local/bin/docker-compose

  dnf install --assumeyes git
}

setup () {
  # Configure Magento's composer auth
  mkdir -p ~/.composer/
  (
    umask 077
    cat <<EOF > ~/.composer/auth.json
{
    "http-basic": {
        "repo.magento.com": {
            "username": "${MAGENTO_REPO_KEY}",
            "password": "${MAGENTO_REPO_SECRET}"
        }
    }
}
EOF
  )

  #git clone https://github.com/solvedata/plugins-magento2 ~/plugins-magento2
  cd ~/plugins-magento2

  sed -i'' 's/--interactive --tty//g' ./install.sh
  ./install.sh
}

main () {
  if [[ -z "${MAGENTO_REPO_KEY:-}" ]] || [[ -z "${MAGENTO_REPO_SECRET:-}" ]]; then
    (2>&1 echo 'Require environment variables MAGENTO_REPO_KEY & MAGENTO_REPO_SECRET')
    exit 1
  fi

  if [[ "$(id -u)" == 0 ]]; then
    root_setup
    exec runuser --user vagrant -- "${0}"
  fi

  setup
}

if [[ "${BASH_SOURCE[0]}" = "$0" ]]; then
  main "$@"
fi

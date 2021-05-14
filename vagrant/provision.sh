#!/usr/bin/env bash

set -euo pipefail
[ -n "${DEBUG:-}" ] && set -x
IFS=$'\n\t'

docker_compose_version='1.29.1'

root_setup () {
  # Install docker
  if ! command -v docker >/dev/null; then
    curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
    sh /tmp/get-docker.sh
    rm /tmp/get-docker.sh

    systemctl start docker
    systemctl enable docker
    usermod -aG docker vagrant
    usermod -aG docker www-data
  fi

  # Install docker-compose
  if ! command -v docker-compose >/dev/null; then
    curl -L "https://github.com/docker/compose/releases/download/${docker_compose_version}/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
  fi

  # apt-get update
  # apt-get install --yes git

  usermod --home /home/www-data www-data
  mkdir -p /home/www-data
  chown www-data:www-data /home/www-data

  ln -fs /src /home/www-data/plugins-magento2
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

  # Install a local magento development environment
  MAGENTO_PATH="${HOME}/magento" /src/install.sh

  # Symlink in the mounted plugin source into the magento's project source directory
  rm -rf ~/magento/vendor/solvedata/plugins-magento2
  ln -s /src/ ~/magento/vendor/solvedata/plugins-magento2

  # Perform first time setup and configuration for the magento development environment
  (
    cd ~/magento/vendor/solvedata/plugins-magento2/docker

    if [ ! -f .env ]; then
      sed "s|^NGINX_HOST_SITE_PATH=.*|NGINX_HOST_SITE_PATH=${HOME}/magento|" .env.example > .env
    fi

    ./tools.sh up
    ./tools.sh setup_magento
    ./tools.sh setup_cron
  )
}

main () {
  if [[ -z "${MAGENTO_REPO_KEY:-}" ]] || [[ -z "${MAGENTO_REPO_SECRET:-}" ]]; then
    (2>&1 echo 'Require environment variables MAGENTO_REPO_KEY & MAGENTO_REPO_SECRET')
    exit 1
  fi

  if [[ "$(id -u)" == 0 ]]; then
    root_setup
    chown www-data:www-data "${0}"
    exec runuser --user www-data -- "${0}"
  fi

  setup
}

if [[ "${BASH_SOURCE[0]}" = "$0" ]]; then
  main "$@"
fi

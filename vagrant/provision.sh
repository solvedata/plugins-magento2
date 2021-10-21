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

  apt-get update
  apt-get install --yes git jq moreutils

  usermod --shell /bin/bash --home /home/www-data www-data
  mkdir -p ~www-data
  chown www-data:www-data ~www-data
}

install_magento () {
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
  MAGENTO_PATH="${HOME}/magento" /plugins-magento2/install.sh
}

mount_plugin_source() {
  local mount_dir=~www-data/magento/vendor/solvedata/plugins-magento2

  if ! findmnt -- "${mount_dir}" >/dev/null 2>&1; then
    # Delete the existing plugin directory that composer pulled when resolving dependencies
    rm -rf -- "${mount_dir}"

    mkdir -- "${mount_dir}"
    chown www-data:www-data -- "${mount_dir}"
    mount \
      -t vboxsf \
      -o rw,nodev,relatime,iocharset=utf8,uid=33,gid=33 \
      plugins-magento2 "${mount_dir}"
  fi

  if ! findmnt -- "${mount_dir}/vendor" >/dev/null 2>&1; then
    # We mount the plugin's source directory into Vagrant however we would want to avoid mounting the the vendor directory in from the plugin.
    # This is because otherwise Magento will find the vendor's vendor directory and get confused.
    #
    # Since it's not possible to exclude directories in a mount, we have to do the next best thing and hide the directory by mounting a filesystem over the top.

    if [[ ! -d "${mount_dir}/vendor" ]]; then
      mkdir -p "${mount_dir}/vendor"
      chown www-data:www-data -- "${mount_dir}/vendor"
    fi
    
    mount \
      -t tmpfs \
      -o size=0 \
      plugins-magento2-obscure-vendor "${mount_dir}/vendor"
  fi
}

wait_for_mysql() {
  (
    cd ~/magento/vendor/solvedata/plugins-magento2/docker

    echo "Waiting for mysql to be ready"
    until docker-compose exec -T mysql mysql -e 'select 1' >/dev/null; do
      echo -n '.'
    done
    echo
  )
}

setup_magento() {
  # Perform first time setup and configuration for the magento development environment
  cd ~/magento/vendor/solvedata/plugins-magento2/docker

  if [ ! -f .env ]; then
    sed "s|^NGINX_HOST_SITE_PATH=.*|NGINX_HOST_SITE_PATH=${HOME}/magento|" .env.example > .env
  fi

  ./tools.sh up

  wait_for_mysql

  ./tools.sh setup_magento
  ./tools.sh setup_cron

  ./tools.sh urls
}

main () {
  if [[ -z "${MAGENTO_REPO_KEY:-}" ]] || [[ -z "${MAGENTO_REPO_SECRET:-}" ]]; then
    (2>&1 echo 'Require environment variables MAGENTO_REPO_KEY & MAGENTO_REPO_SECRET')
    exit 1
  fi

  case "${1:-}" in
    '')
      chown www-data:www-data "${0}"

      root_setup

      runuser --user www-data -- "${0}" install_magento

      mount_plugin_source

      runuser --user www-data -- "${0}" setup_magento
      ;;

    install_magento)
      install_magento
      ;;
    
    setup_magento)
      setup_magento
      ;;
    
    *)
      (2>&1 echo "invalid argument ${1}")
      exit 1
      ;;
  esac
}

if [[ "${BASH_SOURCE[0]}" = "$0" ]]; then
  main "$@"
fi

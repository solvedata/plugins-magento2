#!/usr/bin/env bash

set -euxo pipefail
TMP_DIR=$(mktemp -d)


plugin_vcs_name='magento2-plugin'
plugin_composer_name='plugins-magento2'

run_composer() {
  # See https://hub.docker.com/_/composer for details around
  #   using a non-root user and mounting the SSH agent socket.
  docker run --rm --interactive --tty \
    --volume "${COMPOSER_HOME:-$HOME/.composer}:/tmp" \
    --volume "${PWD}:/app" \
    --user "$(id -u):$(id -g)" \
    composer:1.8.0 --profile -vvv "$@" || (
      exit_code=$?
      echo "Exited with code: $exit_code"
      echo "If composer exited part way through without reason, it may have run out of memory"
      echo "Stop all other running containers and try again."
      echo "Composer needs about 1.7GB of memory to install Magento and 0.7GB to install other packages"
      exit 1
    )

}

echo "Enter folder name [magento]:"
MAGENTO_PATH="${MAGENTO_PATH:-magento}"
mkdir -p "$MAGENTO_PATH"
cd "$MAGENTO_PATH" || exit 1

if [ ! -f "composer.json" ]; then
  echo "Start of installing magento..."
  echo "Enter magento version: "
  MAGENTO_VERSION="${MAGENTO_VERSION:-2.3.5}"
  if [ -z "$MAGENTO_VERSION" ]; then
    MAGENTO_COMPOSER="magento/project-community-edition"
  else
    MAGENTO_COMPOSER="magento/project-community-edition=$MAGENTO_VERSION"
  fi

  echo "Downloading magento ($MAGENTO_COMPOSER)..."
  run_composer create-project --prefer-dist --ignore-platform-reqs --repository-url=https://repo.magento.com/ $MAGENTO_COMPOSER .

  echo "Downloading required packages..."

  # Pin kiwicommerce/module-cron-scheduler to v1.0.7 to support Magento versions >= v2.3.5
  run_composer require --prefer-dist --ignore-platform-reqs \
    kiwicommerce/module-cron-scheduler=1.0.7 \
    kiwicommerce/module-admin-activity \
    kiwicommerce/module-login-as-customer
fi

if [ ! -d "./vendor/solvedata/${plugin_composer_name}/.env" ]; then
  echo "Downloading solvedata package..."

  # Require solvedata/plugins-magento2 package with the --no-interaction flag so it will use SSH to clone the repo
  #   rather than prompting for a Github API token.
  # This requires setting up a readonly deploy SSH key in the solvedata/plugins-magento2 repo.

  run_composer config "repositories.${plugin_vcs_name}" vcs "https://github.com/solvedata/${plugin_vcs_name}.git"

  # if [ ! -d ~/".composer/cache/vcs/git-github.com-solvedata-${plugin_vcs_name}.git/" ]; then
  #   # Leet hax. Clone the repo outside of the the composer container so we
  #   #  can use the HTTP endpoint, as Composer(for some reason) refuses to
  #   git clone --mirror "https://github.com/solvedata/${plugin_vcs_name}.git" ~/".composer/cache/vcs/git-github.com-solvedata-${plugin_vcs_name}.git/"
  # fi

  run_composer require --no-interaction --ignore-platform-reqs "solvedata/${plugin_composer_name}"


  ./vendor/solvedata/plugins-magento2/docker/tools.sh install
  ./vendor/solvedata/plugins-magento2/docker/tools.sh up

  echo "Magento has been installed, but if you are running Docker on Linux you may need to change the permissions on the project directory to ensure PHP & Nginx can both read the files and create new temporary files."
else
  ./vendor/solvedata/plugins-magento2/docker/tools.sh down
  echo "Magento is installed..."
  echo "Save docker data to temp..."
  mkdir "${TMP_DIR}/docker"
  cp ./vendor/solvedata/plugins-magento2/docker/.env "${TMP_DIR}/docker/.env"

  echo "Update solvedata package..."
  run_composer update --ignore-platform-reqs "solvedata/${plugin_composer_name}"

  echo "Load saved docker data..."
  cp -a "${TMP_DIR}/docker/." "./vendor/solvedata/${plugin_composer_name}/docker"

  echo "Remove installer temp files"
  rm -R "${TMP_DIR}"

  ./vendor/solvedata/plugins-magento2/docker/tools.sh up
  ./vendor/solvedata/plugins-magento2/docker/tools.sh recompile
fi

#!/usr/bin/env bash

set -x

# Start an SSH agent session and add all keys to into it
eval "$(ssh-agent -s)"
find ~/.ssh -maxdepth 1 -name 'id_*' -not -name 'id_*.pub' -exec ssh-add {} \;

USER_GROUP_ID="$(id -u):$(id -g)"
plugin_vcs_name='magento2-plugin'
plugin_composer_name='plugins-magento2'

run_composer() {
  # See https://hub.docker.com/_/composer for details around
  #   using a non-root user and mounting the SSH agent socket.
  docker run --rm --interactive --tty \
    --volume "${COMPOSER_HOME:-$HOME/.composer}:/tmp" \
    --volume "${PWD}:/app" \
    --user "${USER_GROUP_ID}" \
    composer:1.8.0 -vvv "$@"
}

echo "Enter folder name [magento]:"
# read MAGENTO_PATH
MAGENTO_PATH="${MAGENTO_PATH:-magento}"
mkdir -p "$MAGENTO_PATH"
cd "$MAGENTO_PATH"

if [ ! -f "composer.json" ]; then
  echo "Start of installing magento..."
  echo "Enter magento version: "
  export MAGENTO_VERSION=2.3.5
  if [ -z "$MAGENTO_VERSION" ]; then
    MAGENTO_COMPOSER="magento/project-community-edition"
  else
    MAGENTO_COMPOSER="magento/project-community-edition=$MAGENTO_VERSION"
  fi

  echo "Downloading magento ($MAGENTO_COMPOSER)..."
  run_composer create-project --ignore-platform-reqs --repository-url=https://repo.magento.com/ $MAGENTO_COMPOSER .
fi

if [ ! -d "./vendor" ]; then
  echo "Downloading required packages..."

  # Pin kiwicommerce/module-cron-scheduler to v1.0.7 to support Magento versions >= v2.3.5
  run_composer require --ignore-platform-reqs kiwicommerce/module-cron-scheduler=1.0.7
  run_composer require --ignore-platform-reqs kiwicommerce/module-admin-activity
  run_composer require --ignore-platform-reqs kiwicommerce/module-login-as-customer

  exit 1
fi

if [ ! -d "./vendor/solvedata/${plugin_composer_name}/.env" ]; then
  echo "Downloading solvedata package..."

  # Require solvedata/plugins-magento2 package with the --no-interaction flag so it will use SSH to clone the repo
  #   rather than prompting for a Github API token.
  # This requires setting up a readonly deploy SSH key in the solvedata/plugins-magento2 repo.

  run_composer -vvv config "repositories.${plugin_vcs_name}" vcs "https://github.com/solvedata/${plugin_vcs_name}.git"

  # if [ ! -d ~/".composer/cache/vcs/git-github.com-solvedata-${plugin_vcs_name}.git/" ]; then
  #   # Leet hax. Clone the repo outside of the the composer container so we
  #   #  can use the HTTP endpoint, as Composer(for some reason) refuses to
  #   git clone --mirror "https://github.com/solvedata/${plugin_vcs_name}.git" ~/".composer/cache/vcs/git-github.com-solvedata-${plugin_vcs_name}.git/"
  # fi

  run_composer -vvv require --no-interaction --ignore-platform-reqs "solvedata/${plugin_composer_name}"

  if [ -f '../install.sh' ]; then
    # Link the docker things to the docker things in this directory.
    rm -r ./vendor/solvedata/plugins-magento2/docker || true
    ln -s ../../../ ./vendor/solvedata/plugins-magento2/docker
  fi


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
  run_composer update --ignore-platform-reqs solvedata/plugins-magento2

  echo "Load saved docker data..."
  cp -a "${TMP_DIR}/docker/." ./vendor/solvedata/plugins-magento2/docker

  echo "Remove installer temp files"
  rm -R "${TMP_DIR}"

  ./vendor/solvedata/plugins-magento2/docker/tools.sh up
  ./vendor/solvedata/plugins-magento2/docker/tools.sh recompile
fi

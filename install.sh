#!/usr/bin/env bash

TMP_DIR=$(mktemp -d)

run_composer() {
  # See https://hub.docker.com/_/composer for details around using a non-root user.
  docker run --rm --interactive --tty \
    --volume "${COMPOSER_HOME:-$HOME/.composer}:/tmp" \
    --volume "${PWD}:/app" \
    --volume /etc/passwd:/etc/passwd:ro \
    --volume /etc/group:/etc/group:ro \
    --user "$(id -u):$(id -g)" \
    composer:1.8.0 $1
}

if [[ -z "${MAGENTO_PATH:-}" ]]; then
  echo "Enter folder name [magento]:"
  read -r MAGENTO_PATH
fi

MAGENTO_PATH="${MAGENTO_PATH:-magento}"
mkdir -p -- "${MAGENTO_PATH}"
cd -- "${MAGENTO_PATH}" || exit 1

if [[ ! -f "composer.json" ]]; then
  echo "Start of installing magento..."

  if [[ -z "${MAGENTO_VERSION:-}" ]]; then
    echo "Enter magento version: "
    read -r MAGENTO_VERSION
  fi

  if [[ -z "$MAGENTO_VERSION" ]]; then
    MAGENTO_COMPOSER="magento/project-community-edition"
  else
    MAGENTO_COMPOSER="magento/project-community-edition=$MAGENTO_VERSION"
  fi

  echo "Downloading magento ($MAGENTO_COMPOSER)..."
  run_composer "create-project --ignore-platform-reqs --repository-url=https://repo.magento.com/ $MAGENTO_COMPOSER ."

  echo "Downloading required packages..."

  # Pin kiwicommerce/module-cron-scheduler to v1.0.7 to support Magento versions >= v2.3.5
  run_composer "require --ignore-platform-reqs kiwicommerce/module-cron-scheduler=1.0.7"
  run_composer "require --ignore-platform-reqs kiwicommerce/module-admin-activity"
  run_composer "require --ignore-platform-reqs kiwicommerce/module-login-as-customer"

  echo "Downloading solvedata package..."

  run_composer "require --ignore-platform-reqs solvedata/plugins-magento2"

  echo "Remove installer temp files"
  rm -R "${TMP_DIR}"

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
  run_composer "update --ignore-platform-reqs solvedata/plugins-magento2"

  echo "Load saved docker data..."
  cp -a "${TMP_DIR}/docker/." ./vendor/solvedata/plugins-magento2/docker

  echo "Remove installer temp files"
  rm -R "${TMP_DIR}"

  ./vendor/solvedata/plugins-magento2/docker/tools.sh up
  ./vendor/solvedata/plugins-magento2/docker/tools.sh recompile
fi

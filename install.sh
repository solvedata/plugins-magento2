#!/usr/bin/env bash

TMP_DIR=$(mktemp -d)

# Start an SSH agent session and add all keys to into it
eval "$(ssh-agent -s)"
find ~/.ssh -maxdepth 1 -name 'id_*' -not -name 'id_*.pub' -exec ssh-add {} \;

run_composer() {
  # Add github's host key to composer's known hosts file if it doesn't exist
  test -f "${TMP_DIR}/ssh_known_hosts" \
    || echo 'github.com ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAq2A7hRGmdnm9tUDbO9IDSwBK6TbQa+PXYPCPy6rbTrTtw7PHkccKrpp0yVhp5HdEIcKr6pLlVDBfOLX9QUsyCOV0wzfjIJNlGEYsdlLJizHhbn2mUjvSAHQqZETYP81eFzLQNnPHt4EVVUh7VfDESU84KezmD5QlWpXLmvU31/yMf+Se8xhHTvKSCZIFImWwoG6mbUoWf9nzpIoaSjB+weqqUUmpaaasXVal72J+UX2B+2RPW3RcT0eOzQgqlJL3RKrTJvdsjE3JEAvGq3lGHSZXy28G3skua2SmVi/w4yCE6gbODqnTWlg7+wC604ydGXA8VJiS5ap43JXiUFFAaQ==' > "${TMP_DIR}/ssh_known_hosts"
  
  # See https://hub.docker.com/_/composer for details around
  #   using a non-root user and mounting the SSH agent socket.
  docker run --rm --interactive --tty \
    --env SSH_AUTH_SOCK=/ssh-auth.sock \
    --volume "${COMPOSER_HOME:-$HOME/.composer}:/tmp" \
    --volume "${PWD}:/app" \
    --volume "${SSH_AUTH_SOCK}:/ssh-auth.sock" \
    --volume "${TMP_DIR}/ssh_known_hosts:/etc/ssh/ssh_known_hosts" \
    --volume /etc/passwd:/etc/passwd:ro \
    --volume /etc/group:/etc/group:ro \
    --user "$(id -u):$(id -g)" \
    composer:1.8.0 $1
}

echo "Enter folder name [magento]:"
read MAGENTO_PATH
MAGENTO_PATH="${MAGENTO_PATH:-magento}"
mkdir $MAGENTO_PATH
cd $MAGENTO_PATH

if [ ! -f "composer.json" ]; then
  echo "Start of installing magento..."
  echo "Enter magento version: "
  read MAGENTO_VERSION
  if [ -z "$MAGENTO_VERSION" ]; then
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

  # Require solvedata/plugins-magento2 package with the --no-interaction flag so it will use SSH to clone the repo
  #   rather than prompting for a Github API token.
  # This requires setting up a readonly deploy SSH key in the solvedata/plugins-magento2 repo.
  run_composer "config repositories.repo-name vcs git@github.com:solvedata/plugins-magento2.git"
  run_composer "require --no-interaction --ignore-platform-reqs solvedata/plugins-magento2"

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

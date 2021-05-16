#!/usr/bin/env bash

set -x

DIR=$(dirname "$0")

declare command="${1}"
shift

case "${command}" in

    up)
        echo "Starting docker..."
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" up -d
    ;;

    down)
        echo "Stopping docker..."
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" down
    ;;

    restart)
        echo "Restarting docker..."
        "${DIR}/tools.sh" down
        "${DIR}/tools.sh" up
    ;;

    build)
        echo "Building docker..."
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" build
    ;;

    rebuild)
        echo "Rebuilding docker..."
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" up -d --build --force-recreate
    ;;

    php)
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" exec php-fpm
    ;;

    mysql)
        # shellcheck disable=SC2016
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" exec mysql mysql "$@"
    ;;

    php_exec)
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" exec php-fpm "$@"
    ;;

    recompile)
        echo "Regenerating Magento's generated code & content..."
        bash "${DIR}/tools.sh" php_exec sudo -u www-data php bin/magento setup:upgrade
        bash "${DIR}/tools.sh" php_exec sudo -u www-data php bin/magento setup:di:compile
        bash "${DIR}/tools.sh" php_exec sudo -u www-data php bin/magento setup:static-content:deploy --force
    ;;

    setup_magento)
        echo "Performing first time setup of Magento"

        # shellcheck disable=SC1004
         docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" exec -T php-fpm sh -c '
            sudo -u www-data php bin/magento setup:install \
                --base-url="http://${MAGENTO_WEB_ADDRESS}:${MAGENTO_WEB_PORT}/" \
                --db-host="${MYSQL_HOST}" \
                --db-name="${MYSQL_DATABASE}" \
                --db-user="${MYSQL_USER}" \
                --db-password="${MYSQL_PASSWORD}" \
                --admin-firstname="${MAGENTO_ADMIN_FIRSTNAME}" \
                --admin-lastname="${MAGENTO_ADMIN_LASTNAME}" \
                --admin-email="${MAGENTO_ADMIN_EMAIL}" \
                --admin-user="${MAGENTO_ADMIN_USER}" \
                --admin-password="${MAGENTO_ADMIN_PASSWORD}" \
                --language="${MAGENTO_LANGUAGE}" \
                --currency="${MAGENTO_CURRENCY}" \
                --timezone="${MAGENTO_TIMEZONE}"
            '
    ;;

    setup_cron)
        echo "Starting magento cron jobs..."
        "${DIR}/tools.sh" php_exec sudo -u www-data php bin/magento cache:flush
        "${DIR}/tools.sh" php_exec sudo -u www-data php bin/magento cron:install
        "${DIR}/tools.sh" php_exec sudo -u www-data php bin/magento cron:run
        "${DIR}/tools.sh" php_exec service rsyslog start
        "${DIR}/tools.sh" php_exec service cron restart
    ;;

    admin_url)
        bash "${DIR}/tools.sh" php_exec sudo -u www-data php bin/magento info:adminuri
    ;;

    *)
        echo "
        docker:
            ${DIR@Q}/tools.sh up
            ${DIR@Q}/tools.sh down
            ${DIR@Q}/tools.sh restart
            ${DIR@Q}/tools.sh pull
            ${DIR@Q}/tools.sh build
            ${DIR@Q}/tools.sh rebuild

        containers:
            ${DIR@Q}/tools.sh php
            ${DIR@Q}/tools.sh mysql

        tools:
            ${DIR@Q}/tools.sh php_exec ...
            ${DIR@Q}/tools.sh mysql_exec ...

        magento:
            ${DIR@Q}/tools.sh setup_mageneto
            ${DIR@Q}/tools.sh setup_cron
            ${DIR@Q}/tools.sh recompile
            ${DIR@Q}/tools.sh admin_url
        "
        exit 1
    ;;
esac

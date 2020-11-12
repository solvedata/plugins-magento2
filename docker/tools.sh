#!/usr/bin/env bash

set -x

DIR=$(dirname "$0")

case $1 in

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

    pull)
        echo "Pulling docker..."
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" pull
    ;;

    build)
        echo "Building docker..."
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" build
    ;;

    rebuild)
        echo "Rebuilding docker..."
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" up -d --build --force-recreate
    ;;

    install)
        HOST_FILE=/etc/hosts
        if [ ! -f "$HOST_FILE" ]; then
          HOST_FILE="c:\windows\system32\drivers\etc\hosts"
        fi

        HOST_NAME="solvedata.local"
        HOST_ROW="127.0.0.1 solvedata.local"
        if [ ! -n "$(grep "$HOST_NAME" $HOST_FILE)" ] ; then
            echo "You should add this line to your to hosts(after >>):";
            echo ">> $HOST_ROW"
            # sudo -- sh -c -e "echo '\n$HOST_ROW\n' >> $HOST_FILE";
        fi

        if [ ! -f "${DIR}/.env" ]; then
          cp "${DIR}/.env.example" "${DIR}/.env"
        fi

        "${DIR}/tools.sh" pull
    ;;

    php)
        docker exec -it php-fpm sh
    ;;

    mysql)
        docker exec -it mysql sh
    ;;

    php_exec)
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" exec php-fpm $2
    ;;

    mysql_exec)
        docker-compose -f "${DIR}/docker-compose.yml" --project-directory="${DIR}" exec mysql $2
    ;;

    run_cron)
        echo "Starting magento cron jobs..."
        "${DIR}/tools.sh" php_exec "sudo -u www-data php bin/magento cache:flush"
        "${DIR}/tools.sh" php_exec "sudo -u www-data php bin/magento cron:install"
        "${DIR}/tools.sh" php_exec "sudo -u www-data php bin/magento cron:run"
        "${DIR}/tools.sh" php_exec "service rsyslog start"
        "${DIR}/tools.sh" php_exec "service cron restart"
    ;;

    recompile)
        echo "Regenerating Magento's generated code & content..."
        bash "${DIR}/tools.sh" php_exec "sudo -u www-data php bin/magento setup:upgrade"
        bash "${DIR}/tools.sh" php_exec "sudo -u www-data php bin/magento setup:di:compile"
        bash "${DIR}/tools.sh" php_exec "sudo -u www-data php bin/magento setup:static-content:deploy --force"
    ;;

    admin_url)
        bash "${DIR}/tools.sh" php_exec "sudo -u www-data php bin/magento info:adminuri"
    ;;

    *)
        echo "
        docker:
            '${DIR}/tools.sh' up
            '${DIR}/tools.sh' down
            '${DIR}/tools.sh' restart
            '${DIR}/tools.sh' pull
            '${DIR}/tools.sh' build
            '${DIR}/tools.sh' rebuild
            '${DIR}/tools.sh' install

        containers:
            '${DIR}/tools.sh' php
            '${DIR}/tools.sh' mysql

        tools:
            '${DIR}/tools.sh' php_exec ...
            '${DIR}/tools.sh' mysql_exec ...

        magento:
            '${DIR}/tools.sh'  run_cron
            '${DIR}/tools.sh'  recompile
            '${DIR}/tools.sh'  admin_url
        "
    ;;
esac

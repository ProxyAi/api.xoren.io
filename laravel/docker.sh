#!/bin/bash

#######################################################################
# Laravel Application Startup Script
#######################################################################
#
# This script is used to install Laravel Swoole application dependencies,
# configure the environment, and start the necessary processes based
# on the specified environment (local or production). It supports both
# single-container deployment for production and separated containers
# for local development.
#
# Usage:
#   ./startup.sh
#
# Environment Variables:
#   - APP_ENV: Specifies the environment (e.g., "local" or "production").
#              Defaults to "local" if not set.
#   - CONTAINER_ROLE: Specifies the container role, such as "laravel",
#                     "queue", "queue_high", "queue_low", or "schedule".
#                     Defaults to "laravel" if not set.
#
# Dependencies:
#   - Composer, PHP, Node.js, and npm are required to be installed on the system.
#   - Supervisord is used for process control in production.
#
# Functions:
#   - _install_app_packages: Install Laravel application dependencies.
#   - _wait_for_packages: Wait for composer installation to finish.
#
#######################################################################

# Function: _install_app_packages
# Description: Function to install vendor packages via composer.
# Parameters: None
# Returns: None

_install_app_packages() {
    # Check for an in-progress installation.
    touch packages.installing.txt

    # Add PATH to the user's .bashrc if not already present.
    if ! grep -q "export PATH=\"$PATH:$HOME/bin\"" "$HOME/.bashrc"; then
        echo "export PATH=\"$PATH:$HOME/bin\"" >>~/.bashrc
    fi

    echo "[INFO] Installing PHP and Node.js packages via composer and npm."

    # Check the environment and install packages accordingly.
    if [[ "${APP_ENV:-local}" = "production" ]]; then
        composer install --quiet --prefer-dist --no-progress --no-dev
        composer update --quiet --prefer-dist --no-progress --no-dev
        npm install --silent --no-progress --production
    else
        composer install --no-progress # --profile
        composer update --no-progress  # --profile
        npm install --silent --no-progress
    fi
    # Source the .bashrc file.
    source "$HOME/.bashrc"
    # Remove the in-progress indicator.
    rm packages.installing.txt
}

# Function: _wait_for_packages
# Description: Wait for composer installation to finish.
# Parameters: None
# Returns: None

_wait_for_packages() {
    while [[ -f "packages.installing.txt" ]]; do
        sleep 6s
    done
}

# Function: _config_php_ports
# Description: Dynamic configuration of php ports.
# Parameters: None
# Returns: None

_config_php_ports() {
    PHP_CONFIG_DOCKER=/usr/local/etc/php-fpm.d/docker.conf
    PHP_CONFIG_WWW=/usr/local/etc/php-fpm.d/www.conf
    PHP_CONFIG_ZZ_DOCKER=/usr/local/etc/php-fpm.d/zz-docker.conf

    # Replace user and group in docker.conf
    if [[ -f "$PHP_CONFIG_DOCKER" ]]; then
        sed -i 's/^\(user\s*=\s*\).*$/\1'"${APP_USER:-laravel}"'/; s/^\(group\s*=\s*\).*$/\1'"${APP_USER:-laravel}"'/' "$PHP_CONFIG_DOCKER"
    fi

    # Replace listen address in www.conf
    if [[ -f "$PHP_CONFIG_WWW" ]]; then
        sed -i 's/^\(listen\s*=\s*\).*$/\1'"127.0.0.1:${PHP_PORT:-9000}"'/' "$PHP_CONFIG_WWW"
    fi

    # Replace listen port in zz-docker.conf
    if [[ -f "$PHP_CONFIG_ZZ_DOCKER" ]]; then
        sed -i 's/^\(listen\s*=\s*\).*$/\1'"${PHP_PORT:-9000}"'/' "$PHP_CONFIG_ZZ_DOCKER"
    fi
}

# Production mode
if [[ "${APP_ENV:-local}" != "local" ]]; then
    # Dynamic configuration of php ports.
    _config_php_ports
    # Install required packages.
    _install_app_packages

    # Create a symbolic link for the storage directory.
    php artisan storage:link

    # Set supervisord configuration file path.
    SUPERVISORD_CONF=/var/www/supervisord/production.conf

    SWOOLE_CMD="/usr/local/bin/php -d variables_order=EGPCS /var/www/artisan octane:start --server=swoole --host=0.0.0.0"
    SWOOLE_CMD="${SWOOLE_CMD} --port=${SWOOLE_PORT:-9000}"
    SWOOLE_CMD="${SWOOLE_CMD} --workers=${SWOOLE_WORKERS:-auto}"
    SWOOLE_CMD="${SWOOLE_CMD} --task-workers=${SWOOLE_TASK_WORKERS:-auto}"
    SWOOLE_CMD="${SWOOLE_CMD} --max-requests=${SWOOLE_MAX_REQUESTS:-500}"

    TRUES=(true 'true' "true" "True" "TRUE")

    # for val in "${TRUES[@]}"; do
    #     if [[ "${SWOOLE_WATCH:-false}" == "$val" ]]; then
    #         SWOOLE_CMD=${SWOOLE_CMD}" --watch"
    #         break
    #     fi
    # done

    sed -i '/\[program:laravel\]/,/\[/{/^command =/s#\(^command = \).*#\1'"$SWOOLE_CMD"'#}' "$SUPERVISORD_CONF"

    # Change user and chown in supervisord config file.
    sed -i 's/\(user\s*=\s*\).*$/\1'"$APP_USER"'/;s/\(chown\s*=\s*\).*$/\1'"$APP_USER:$APP_USER"'/' "$SUPERVISORD_CONF"

    # Start supervisord.
    /usr/bin/supervisord -u "$APP_USER" -n -c "$SUPERVISORD_CONF"
    /usr/bin/supervisorctl start all

    # Exit the script after starting supervisord
    exit
fi

# If in the local environment, laravel runtime is in separated containers.
case "${CONTAINER_ROLE:-laravel}" in
"laravel")

    # Dynamic configuration of php ports.
    _config_php_ports

    # Install required packages.
    _install_app_packages

    # Create a symbolic link for the storage directory.
    php artisan storage:link

    if [[ $SWOOLE_WATCH == "false" ]]; then
        /usr/local/bin/php -d variables_order=EGPCS /var/www/artisan octane:start \
            --server=swoole \
            --host=0.0.0.0 \
            --port=${SWOOLE_PORT:-9000} \
            --workers=${SWOOLE_WORKERS:-auto} \
            --task-workers=${SWOOLE_TASK_WORKERS:-auto} \
            --max-requests=${SWOOLE_MAX_REQUESTS:-500}
    else
        /usr/local/bin/php -d variables_order=EGPCS /var/www/artisan octane:start \
            --server=swoole \
            --host=0.0.0.0 \
            --port=${SWOOLE_PORT:-9000} \
            --workers=${SWOOLE_WORKERS:-auto} \
            --task-workers=${SWOOLE_TASK_WORKERS:-auto} \
            --max-requests=${SWOOLE_MAX_REQUESTS:-500} \
            --watch
    fi
    ;;
*)
    # For queue low, default, high, and schedule runtimes.

    # Run function to wait for composer installation to finish.
    _wait_for_packages

    # Source the .bashrc file.
    source "$HOME/.bashrc"

    case "${CONTAINER_ROLE:-laravel}" in
    "queue")
        # Run Laravel queue listener with specified options.
        while true; do
            nice -n 10 /usr/local/bin/php -d variables_order=EGPCS /var/www/artisan queue:listen --sleep=5 --tries=3 --queue=default --rest=0.4 --timeout=3600 --memory=1024
            sleep 10s
        done
        ;;
    "queue_high")
        # Run Laravel high-priority queue listener with specified options.
        while true; do
            nice -n 10 /usr/local/bin/php -d variables_order=EGPCS /var/www/artisan queue:listen --sleep=5 --tries=3 --queue=high --rest=0.4 --timeout=3600 --memory=1024
            sleep 10s
        done
        ;;
    "queue_low")
        # Run Laravel low-priority queue listener with specified options.
        while true; do
            nice -n 10 /usr/local/bin/php -d variables_order=EGPCS /var/www/artisan queue:listen --sleep=5 --tries=3 --queue=low --rest=0.4 --timeout=3600 --memory=1024
            sleep 10s
        done
        ;;
    "schedule")
        # Run Laravel scheduled tasks with specified options.
        echo "" >>/var/www/storage/logs/schedule.log
        while true; do
            /usr/local/bin/php -d variables_order=EGPCS /var/www/artisan schedule:run --verbose --no-interaction --quiet >>/var/www/storage/logs/schedule.log 2>&1
            sleep 10s
        done
        ;;
    *)
        # Log an error if an invalid CONTAINER_ROLE is specified.
        echo "[ERROR] ${CONTAINER_ROLE:-laravel}"
        return
        ;;
    esac
    ;;
esac

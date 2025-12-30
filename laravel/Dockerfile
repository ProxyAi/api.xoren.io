# Base image
FROM php:8.3-fpm

# Build arguments.
ARG APP_ENV=${APP_ENV:-local}
ARG APP_USER=${APP_USER:-laravel}
ARG APP_USER_UUID=${APP_USER_UUID:-1000}
ARG APP_USER_GUID ${APP_USER_GUID:-1000}

# Set timezone.
# Set timezone.
ARG TZ=Europe/London

# Set locale settings.
ARG LANG=en_GB

ARG LCFILE=/etc/default/locale
ARG LGFILE=/etc/locale.gen

ARG NODE_VERSION=${NODE_VERSION:-22}
ARG SWOOLE_PORT=${SWOOLE_PORT:-8000}

# Environment variables.
ENV APP_ENV ${APP_ENV:-local}
ENV APP_USER ${APP_USER:-laravel}
ENV APP_USER_UUID ${APP_USER_UUID:-1000}
ENV APP_USER_GUID ${APP_USER_GUID:-1000}
ENV DEBIAN_FRONTEND noninteractive


# Set timezone.
ARG TZ=${TZ:-Europe/London}
ENV TZ ${TZ}

# Set locale settings.
ARG LANG=${TZ:-en_GB}
ENV LANG ${LANG}

# Swoole Environment variables.
ENV SWOOLE_MAX_REQUESTS ${SWOOLE_MAX_REQUESTS:-500}
ENV SWOOLE_TASK_WORKERS ${SWOOLE_TASK_WORKERS:-auto}
ENV SWOOLE_WATCH ${SWOOLE_WATCH:-false}
ENV SWOOLE_WORKERS ${SWOOLE_WORKERS:-auto}
ENV SWOOLE_PORT ${SWOOLE_PORT:-8000}


# Update and upgrade system packages, then install required system packages.
RUN apt-get update && apt-get upgrade -y -q && \
    apt-get install -y -q apt-utils dialog locales tzdata \
    build-essential ca-certificates cmake curl gnupg default-mysql-client \
    autoconf dpkg-dev dpkg file g++ gcc libc-dev make pkgconf re2c \
    git glew-utils gosu libcap2-bin libfreetype6-dev \
    libglew-dev libjpeg-dev libonig-dev libpng-dev libx11-dev parallel \
    libxcursor-dev libxi6 libxinerama-dev libxml2-dev libxrandr-dev \
    ncat nasm pngquant procps python3 python3-pip libgmp-dev zlib1g-dev \
    libcsv-dev libzip-dev libzip-dev unzip zip \
    sqlite3 sudo supervisor wget imagemagick geoip-bin && \
    apt-get install -y -q --no-install-recommends mariadb-client && \
    rm -rf /var/lib/apt/lists/*

# Set timezone and localization.
RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime && echo "$TZ" > /etc/timezone
# && \
RUN echo "$TZ" > /etc/timezone && \
    echo "en_US.UTF-8 UTF-8" > $LGFILE && \
    echo ${LANG}".UTF-8 UTF-8" >> $LGFILE && \
    echo "LANG="${LANG}".UTF-8 UTF-8" > $LCFILE && \
    for lc_var in ADDRESS IDENTIFICATION MEASUREMENT MONETARY NAME NUMERIC PAPER TELEPHONE TIME; \
    do echo "LC_${lc_var}=${LANG}.UTF-8 UTF-8" >> $LCFILE; done
RUN dpkg-reconfigure --frontend noninteractive tzdata && locale-gen "${LANG}.UTF-8 .UTF-8" && update-locale

# Install PHP extensions.
RUN docker-php-ext-install pdo_mysql gd ctype exif pcntl bcmath && \
    pecl install redis zip csv swoole && \
    docker-php-ext-enable redis zip gd csv swoole

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Nodejs
RUN mkdir -p /etc/apt/keyrings && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
RUN echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_"${NODE_VERSION}".x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list
RUN apt-get update && apt-get -y -q install nodejs

RUN sed -i 's/UID_MIN\t\t1000/UID_MIN\t\t900/' /etc/login.defs 
    # && \
    # sed -i 's/UID_MAX\t\t60000/UID_MAX\t\t49999/' /etc/login.defs

# Ensure the group with the specified GID exists
RUN groupadd -g $APP_USER_GUID $APP_USER || true

# Create system user
RUN useradd -ms /bin/bash -g $APP_USER_GUID -G users,www-data,sudo -u $APP_USER_UUID -d /home/$APP_USER $APP_USER && \
    mkdir -p /home/$APP_USER/.composer /var/www/vendor 
    # && \
    # chown -R $APP_USER:$APP_USER /home/$APP_USER /var/www/vendor && \
    # { \
    #     echo "root ALL=(ALL) ALL"; \
    #     echo "$APP_USER ALL=(ALL) NOPASSWD: ALL"; \
    #     echo "%sudo ALL=(ALL) ALL"; \
    #     echo "@includedir /etc/sudoers.d"; \
    #     echo "Defaults insults"; \
    # } > /etc/sudoers

# Remove packages no longer needed to reduce container size.
RUN apt-get purge -y g++ gcc make\
    build-essential cmake && \
    apt-get autoremove -y && apt-get autoclean -y && \
    rm -rf /var/lib/apt/lists/*

# Copy Laravel project
# COPY laravel/ /var/www
RUN chown -R $APP_USER:$APP_USER /home/$APP_USER /var/www
COPY --chown=$APP_USER:$APP_USER laravel/docker.sh /var/www/docker.sh

# Copy Php config
COPY php/php-ini-overrides.ini /usr/local/etc/php/php.ini
COPY --chown=$APP_USER:$APP_USER php/php-fpm.d/$APP_ENV/* /usr/local/etc/php-fpm.d/

# Reset Environment variables.
ENV DEBIAN_FRONTEND dialog

WORKDIR /var/www

USER $APP_USER

# Expose the ports that Octane is using.
EXPOSE $SWOOLE_PORT

CMD ["bash", "/var/www/docker.sh"]

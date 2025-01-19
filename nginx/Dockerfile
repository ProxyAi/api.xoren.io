FROM nginx:alpine

LABEL maintainer="John J <john@xoren.io>"

# Build arguments.
ARG TZ=Europe/London
ARG LANG=en_GB
# ARG LCFILE=/etc/default/locale
# ARG LGFILE=/etc/locale.gen

# Environment variables.
ENV DEBIAN_FRONTEND noninteractive

# Set timezone.
ENV TZ ${TZ}

# Set locale settings.
ENV LANG ${LANG}
ENV LANGUAGE ${LANG}.UTF-8
ENV LC_ALL ${LANG}.UTF-8

ENV NGINX_BLOCK_BOTS ${NGINX_BLOCK_BOTS:-0}
ENV NGINX_BLOCK_SCANNERS ${NGINX_BLOCK_SCANNERS:-0}

RUN  rm -R /etc/nginx/conf.d && mkdir -p /etc/nginx/snippets/
COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/snippets/*.conf /etc/nginx/snippets/
COPY nginx/conf.d /etc/nginx/conf.d

# Update and upgrade system packages, then install required system packages.
RUN apk update \
    && apk upgrade \
    && apk --update add logrotate \
    && apk add --no-cache openssl \
    && apk add --no-cache bash \
    && apk add --no-cache --update musl musl-utils musl-locales tzdata \
    && apk add --no-cache ca-certificates \
    && apk add --no-cache curl wget

# Set timezone and localization.
RUN echo "export LC_ALL=${LANG}" >> /etc/profile.d/locale.sh && \
    sed -i 's|LANG=C.UTF-8|LANG='$LANG'|' /etc/profile.d/locale.sh
# && \
# openssl req -x509 -nodes -newkey rsa:4096 \
# -keyout /etc/ssl/private/ssl-cert-snakeoil.key \
# -out /etc/ssl/certs/ssl-cert-snakeoil.pem -days 3650 \
# -subj "/C=${APP_ENCODE: -2}/ST=$(echo "$APP_TIMEZONE" | cut -d'/' -f2)/L=$(echo "$APP_TIMEZONE" | cut -d'/' -f2)/O=CompanyName/OU=IT Department/CN=example.com" && \
# openssl dhparam -out /etc/nginx/dhparam.pem 2048

# Create system user
RUN set -x ; \
    addgroup -g 82 -S www-data ; \
    adduser -u 82 -D -S -G www-data www-data && exit 0 ; exit 1

# Create 'messages' file used from 'logrotate'
RUN touch /var/log/messages

# Copy 'logrotate' config file
COPY nginx/logrotate/nginx /etc/logrotate.d/
#COPY --chown=www-data:www-data laravel/storage /var/www/storage
#COPY --chown=www-data:www-data laravel/public /var/www/public

COPY nginx/docker.sh /opt/docker.sh

# removes windows line ending
RUN sed -i 's/\r//g' /opt/docker.sh

# Remove packages no longer needed to reduce container size
RUN rm -rf /var/lib/apt/lists/*

# Reset Environment variables.
ENV DEBIAN_FRONTEND dialog

EXPOSE 80 81 443

CMD ["/bin/bash", "/opt/docker.sh"]
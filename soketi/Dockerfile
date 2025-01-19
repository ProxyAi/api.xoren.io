FROM quay.io/soketi/soketi:latest-16-debian

LABEL maintainer="John J <john@xoren.io>"

# Build arguments.
ARG APP_TIMEZONE=Europe/London
ARG APP_ENCODE=en_GB
ARG LCFILE=/etc/default/locale
ARG LGFILE=/etc/locale.gen

# Environment variables.
ENV DEBIAN_FRONTEND noninteractive
ENV TZ ${APP_TIMEZONE}
ENV LANG ${APP_ENCODE}.UTF-8
ENV LANGUAGE ${APP_ENCODE}.UTF-8
ENV LC_ALL ${APP_ENCODE}.UTF-8

# Update and upgrade system packages, then install required system packages.
RUN apt-get update && apt-get install -y -q \
apt-utils dialog locales tzdata ca-certificates


# Set timezone and localization.
RUN ln -snf /usr/share/zoneinfo/${APP_TIMEZONE} /etc/localtime && \
    echo "$APP_TIMEZONE" > /etc/timezone && \
    echo "en_US.UTF-8 UTF-8" > $LGFILE && \
    echo ${APP_ENCODE}".UTF-8 UTF-8" >> $LGFILE && \
    echo "LANG="${APP_ENCODE}".UTF-8" > $LCFILE && \
    for lc_var in ADDRESS IDENTIFICATION MEASUREMENT MONETARY NAME NUMERIC PAPER TELEPHONE TIME; \
    do echo "LC_${lc_var}=${APP_ENCODE}.UTF-8" >> $LCFILE; done && \
    dpkg-reconfigure --frontend noninteractive tzdata && locale-gen ${APP_ENCODE}.UTF-8 && update-locale

COPY config.json /app/config.json

RUN chmod 777 /app/config.json

# Reset Environment variables.
ENV DEBIAN_FRONTEND dialog

WORKDIR /app

EXPOSE 6001

ENTRYPOINT ["node", "/app/bin/server.js", "start", "--config=/app/config.json"]
ARG MYSQL_VERSION
FROM percona:${MYSQL_VERSION:-8}

LABEL maintainer="John J <john@xoren.io>"

# Build arguments.
ARG APP_ENV=production
ARG TZ=Europe/London
ARG LANG=en_GB

# Environment variables.
ENV APP_ENV ${APP_ENV}

# Set timezone.
ENV TZ ${TZ}

# Set locale settings.
ENV LANG ${LANG}
ENV LANGUAGE ${LANG}.UTF-8
ENV LC_ALL ${LANG}.UTF-8

USER root

# Update and install required system packages.
RUN dnf update -y && dnf install -y \
    dnf-utils \
    rocksdb \
    percona-server-rocksdb \
    netcat

    # ca-certificates \
    # tzdata \
    # glibc-langpack-en \
# Set timezone and localization.
# RUN ln -snf /usr/share/zoneinfo/${APP_TIMEZONE} /etc/localtime && \
# RUN echo "$APP_TIMEZONE" > /etc/timezone && \
    # echo "en_US.UTF-8 UTF-8" > $LGFILE 
    # && \
# RUN echo ${APP_ENCODE}".UTF-8 UTF-8" >> $LGFILE && \
    # echo "LANG="${APP_ENCODE}".UTF-8" > $LCFILE

# RUN for lc_var in ADDRESS IDENTIFICATION MEASUREMENT MONETARY NAME NUMERIC PAPER TELEPHONE TIME; \
    # do echo "LC_${lc_var}=${APP_ENCODE}.UTF-8" >> $LCFILE; done && \
RUN ln -sf /usr/share/zoneinfo/${APP_TIMEZONE} /etc/localtime


# Remove packages no longer needed to reduce container size
RUN dnf autoremove -y && dnf clean all

COPY $APP_ENV.cnf /etc/my.cnf.d/custom.cnf

# Change ownership of MySQL data directory
RUN chown -R mysql:mysql /var/lib/mysql/ && \
    mkdir -p /opt chmod 777 /opt && \
    chown mysql:mysql /opt

COPY --chown=mysql:mysql docker.sh /opt/docker.sh

# removes windows line ending
RUN chmod 777 /opt/docker.sh && sed -i 's/\r//g' /opt/docker.sh

USER mysql

EXPOSE 3306 33060

CMD ["mysqld"]

# CMD ["/bin/bash", "/opt/docker.sh"]

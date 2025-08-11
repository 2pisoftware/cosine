# ==========================================================================
# ## Cosine docker image ##
# ==========================================================================

# This image provides a fully working Cosine instance

# It provides the following build arguments:
# - PHP_VERSION: The version of PHP to use
# - UID: The user ID of the cmfive user
# - GID: The group ID of the cmfive group

# NOTE: See the .dockerignore file to see what is excluded from the image.

# Define the Alpine version to use
ARG ALPINE_VERSION=3.19.4

# ==========================================================================
# STAGE 1: Build the theme
# ==========================================================================

FROM --platform=$BUILDPLATFORM node:20 AS theme-build
WORKDIR /var/www/html

# Install dependencies
COPY system/templates/base/package*.json system/templates/base/
RUN cd system/templates/base/ && (npm ci || npm install)

# Build the theme
COPY system/templates/base/ system/templates/base/
RUN cd system/templates/base/ && npm run prod

# ==========================================================================
# STAGE 2: Build the final image
# ==========================================================================

FROM alpine:${ALPINE_VERSION}

# PHP version
# note: see Alpine packages for available versions
ARG PHP_VERSION=81
ENV PHP_VERSION=$PHP_VERSION
ARG UID=1000
ARG GID=1000

# Create cmfive user and group
RUN addgroup -g ${GID} cmfive && \
    adduser -u ${UID} -G cmfive -s /bin/bash -D cmfive

# Link PHP Config
RUN mkdir -p /etc/php && \
    ln -s /etc/php /etc/php$PHP_VERSION

# Install required packages for PHP, Nginx etc
RUN apk --no-cache add \
    php$PHP_VERSION \
    php$PHP_VERSION-fpm \
    php$PHP_VERSION-cli \
    php$PHP_VERSION-curl \
    php$PHP_VERSION-gd \
    php$PHP_VERSION-json \
    php$PHP_VERSION-mbstring \
    php$PHP_VERSION-mysqli \
    php$PHP_VERSION-xml \
    php$PHP_VERSION-zip \
    php$PHP_VERSION-pdo \
    php$PHP_VERSION-pdo_mysql \
    php$PHP_VERSION-phar \
    php$PHP_VERSION-intl \
    php$PHP_VERSION-gettext \
    php$PHP_VERSION-session \
    php$PHP_VERSION-simplexml \
    php$PHP_VERSION-fileinfo \
    nginx \
    mysql-client \
    mariadb-connector-c-dev \
    supervisor \
    bash \
    openssl \
    memcached \
    curl \
    wget \
    unzip \
    icu-data-full \
    git

# Link PHP cli
RUN ln -s /usr/bin/php${PHP_VERSION} /usr/bin/php

# Create necessary directories
RUN mkdir -p /var/www && \
    mkdir -p /run/nginx

# Generate dev/placeholder self-signed SSL certificate
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/nginx.key \
    -out /etc/nginx/nginx.crt \
    -subj "/C=AU/ST=NSW/L=Bega/O=2pisoftware/OU=Development/CN=2pisoftware.com"

# Copy configuration files
COPY /.codepipeline/docker/configs/supervisord/supervisord.conf /etc/supervisord.conf
COPY /.codepipeline/docker/configs/nginx/nginx.conf /etc/nginx/nginx.conf
COPY /.codepipeline/docker/configs/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY /.codepipeline/docker/configs/fpm/ /etc/php/
COPY /.codepipeline/docker/setup.sh /bootstrap/setup.sh
COPY /.codepipeline/docker/start.sh /bootstrap/start.sh
COPY /.codepipeline/docker/config.default.php /bootstrap/config.default.php

# Copy source
COPY --chown=cmfive:cmfive . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Remove .codepipeline
RUN rm -rf .codepipeline

# Create a link to installation tools
RUN ln -s /var/www/html/cmfive.php /usr/local/bin/tools

# Install composer modules
RUN touch /var/www/html/config.php
RUN su cmfive -c 'INSTALL_ENV=docker tools install core'

# Fix permissions
RUN chmod -R ugo=rwX cache/ storage/ uploads/ && \
    chown -R cmfive:cmfive /var/lib/nginx /var/log/nginx

# Install the theme
COPY --from=theme-build /var/www/html/system/templates/base/dist /var/www/html/system/templates/base/dist

# Install startup banner
COPY --chown=cmfive:cmfive /.codepipeline/docker/banner_starting.php /var/www/html/banner.php
COPY --chown=cmfive:cmfive /.codepipeline/docker/banner_starting.php /bootstrap/banner_starting.php
COPY --chown=cmfive:cmfive /.codepipeline/docker/banner_error.php /bootstrap/banner_error.php

# Expose HTTP, HTTPS
EXPOSE 80 443

# Healthcheck to ensure nginx and php-fpm is running and cmfive is installed
HEALTHCHECK --interval=15s --timeout=5m --start-period=5s --retries=15 \
  CMD supervisorctl status nginx | grep -q "RUNNING" && \
      supervisorctl status php-fpm | grep -q "RUNNING" && \
      test -f /home/cmfive/.cmfive-installed
# Start supervisord
CMD ["supervisord", "--nodaemon", "--configuration", "/etc/supervisord.conf"]

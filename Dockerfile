FROM php:7.4-apache

ENV VERSION_ONIG=6.9.3
ENV ONIG_BUILD_DIR=/tmp/oniguruma

RUN set -xe;\
    mkdir -p ${ONIG_BUILD_DIR}; \
    curl -Ls https://github.com/kkos/oniguruma/releases/download/v${VERSION_ONIG}/onig-${VERSION_ONIG}.tar.gz \
    | tar xzC ${ONIG_BUILD_DIR} --strip-components=1; \
    cd ${ONIG_BUILD_DIR}/; \
    ./configure; \
    make -j $(nproc); \
    make install

RUN set -xe; \
    apt update; \
    apt install --yes \
    mariadb-client \
    git \
    zip unzip \
    curl \
    libzip-dev libcurl4-gnutls-dev libpng-dev libxml2-dev libjpeg-dev libfreetype6-dev \
    libmagickwand-dev; \
    #    curl -sL https://deb.nodesource.com/setup_12.x | bash -; \
    #    apt install -y nodejs; \
    docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/; \
    docker-php-ext-install pdo_mysql mysqli gd json zip intl soap mbstring exif bcmath curl sockets; \
    pecl install imagick; \
    docker-php-ext-enable imagick; \
    docker-php-ext-enable mysqli;

VOLUME /var/www/html
COPY app/www/ /var/www/html
COPY app/var /var/www/var
COPY php-ini-overrides.ini /usr/local/etc/php/conf.d/00-php.ini
COPY apache.conf /etc/apache2/sites-available/000-default.conf
VOLUME /ephemeral
RUN mkdir /ephemeral
RUN chown www-data: /ephemeral
VOLUME /shared
RUN mkdir /shared
RUN chown www-data: /shared


# entrypoint sets up the contents of /ephemeral
COPY docker-php-entrypoint /usr/local/bin

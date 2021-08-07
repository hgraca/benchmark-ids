FROM php:8.0-cli-alpine

RUN echo "http://dl-4.alpinelinux.org/alpine/v3.13/main" > /etc/apk/repositories && \
    echo "http://dl-4.alpinelinux.org/alpine/v3.13/community" >> /etc/apk/repositories && \
    apk --update add --no-cache --virtual build-dependencies autoconf g++ && \
    apk add --no-cache make libzip-dev curl && \
    apk add --no-cache bash bash-doc bash-completion && \
    mkdir -p /tmp/pear/cache && \
    pecl install -f xdebug && \
    docker-php-ext-enable xdebug && \
    docker-php-ext-configure pcntl && \
    docker-php-ext-install pcntl && \
    docker-php-ext-configure bcmath && \
    docker-php-ext-install bcmath && \
    docker-php-ext-configure zip && \
    docker-php-ext-install zip && \
    docker-php-ext-install pdo pdo_mysql && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && \
    apk del build-dependencies && \
    rm -rf /var/cache/apk/* && \
    pecl clear-cache

WORKDIR /var/www

CMD ["sleep", "infinity"]

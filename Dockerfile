FROM ubuntu:16.04 as test

ENV DEBIAN_FRONTEND noninteractive

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN set -eux; apt-get update; apt-get -y install git php-cli php-mbstring php-xml; composer install --no-progress --prefer-dist

VOLUME /test-results

CMD ["/app/run-tests.sh"]
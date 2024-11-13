FROM ghcr.io/roadrunner-server/roadrunner:2024 as roadrunner

FROM php:8.3-alpine

# https://github.com/mlocati/docker-php-extension-installer
# https://github.com/docker-library/docs/tree/0fbef0e8b8c403f581b794030f9180a68935af9d/php#how-to-install-more-php-extensions
RUN --mount=type=bind,from=mlocati/php-extension-installer:2,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
     install-php-extensions @composer-2 opcache zip intl sockets protobuf

#RUN --mount=type=bind,from=mlocati/php-extension-installer:2,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
RUN apk add --update --no-cache autoconf libzip-dev libcurl-dev; \
    docker-php-ext-install -j$(nproc) curl json pdo pdo_mysql openssl bcmath curl json mbstring tokenizer xml mysqli; \
    apk del gcc g++ ;\
    rm -rf /var/cache/apk/*
# openssl
# bcmath curl json mbstring tokenizer xml pdo
# pdo_mysql && docker-php-ext-enable pdo_mysql

# # #
# Install grpc extension
# # #
RUN set -eux; \
  apk add --no-cache --virtual .build-deps zlib-dev linux-headers
    
RUN set -eux; \
	MAKEFLAGS="-j $(nproc)" pecl install \
		grpc \
	;

RUN set -eux; \
    apk add --no-cache \
        libstdc++  \
    ;

RUN set -eux; \
    find "$(php-config --extension-dir)" -name '*.so' -type f -exec strip --strip-all {} ';' \
    ;

RUN set -eux; \
    pecl clear-cache \
    ; \
    docker-php-ext-enable \
        grpc \
    ;

RUN set -eux; \
    runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )" \
    ; \
    apk add --no-cache --virtual .api-phpexts-rundeps $runDeps \
    ; \
    apk del .build-deps

# # #
# remove build deps
# # #
#RUN apk del -f .build-dependencies

COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr

EXPOSE 8000/tcp

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy composer files from app directory to install dependencies
COPY ./src/composer.* .

RUN composer install --optimize-autoloader --no-dev --no-scripts

# Copy application files
COPY ./docker/roadrunner/rr.yaml /etc/rr/.rr.yaml

# Run RoadRunner server
#CMD ls -la
CMD rr serve -c /etc/rr/.rr.yaml

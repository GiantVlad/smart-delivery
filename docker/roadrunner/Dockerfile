FROM gaintvlad/php-grpc-rrunner

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

FROM gaintvlad/php84-grpc-rrunner:v2025.1.6

EXPOSE 8000/tcp

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy composer files from app directory to install dependencies
COPY ./src/composer.* .

RUN composer install --optimize-autoloader --no-dev --no-scripts

# Copy application files
COPY ./docker/roadrunner/rr.yaml /etc/rr/.rr.yaml

#Uncoment first time the next line and comment rr serve
#CMD sleep infinity

# Run RoadRunner server
CMD rr serve -c /etc/rr/.rr.yaml

FROM dahirmuhammaddahir/secureng-php-apache

# Create a new non-root user and set it as the default user
RUN groupadd -g 1000 myuser \
    && useradd -u 1000 -g myuser -s /bin/bash -m myuser

USER myuser

# Set the COMPOSER_HOME environment variable to a writable location
ENV COMPOSER_HOME=/home/myuser/.composer

WORKDIR /var/www/html

COPY . .

# Install Composer dependencies
RUN composer update && \
    composer install && \
    cd thirdparty/grpc-fingerprint-engine/src/php/ && \
    composer update && \
    composer install && \
    sudo apt update && sudo apt install -y net-tools

# Switch back to the root user if needed for other operations (not recommended for running Composer)
USER root
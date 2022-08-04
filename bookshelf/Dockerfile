# Use the official PHP image.
# https://hub.docker.com/_/php
FROM php:7.2-cli

# Copy composer bin
COPY --from=composer:2.3.10 /usr/bin/composer /usr/bin/composer

# Copy local code to the container image.
ENV APP_HOME /app
WORKDIR $APP_HOME
COPY . ./

# Install dependencies
RUN composer install

# Run the web service on container startup.
ENTRYPOINT [ "php", "-S", "localhost:8080" ]
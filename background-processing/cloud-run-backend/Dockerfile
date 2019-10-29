FROM gcr.io/gae-runtimes/php72:php72_20190827_7_2_21_RC02

# Install Composer
RUN curl https://getcomposer.org/download/1.7.2/composer.phar > /usr/local/bin/composer \
  && chmod +x /usr/local/bin/composer

WORKDIR /srv/

# Enable PHP gRPC extension and google config
RUN echo "extension=grpc.so" > grpc.ini \
    && mkdir .googleconfig \
    && echo '{"entrypointContents": "serve router.php"}' > .googleconfig/app_start.json

# Copy over Composer files and run "composer install"
COPY composer.* /srv/
RUN composer install --no-dev

# Copy over all application files
COPY . /srv/

# Start NGINX and PHP-FPM
ENTRYPOINT ["/start"]

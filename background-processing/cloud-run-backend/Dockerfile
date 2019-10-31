FROM gcr.io/gae-runtimes/php73:php73_20190827_7_3_7_RC03

WORKDIR /srv/

# Enable PHP gRPC extension
RUN echo "extension=grpc.so" > grpc.ini

# Configure the container to serve to "router.php" instead of to "index.php"
RUN mkdir .googleconfig \
    && echo '{"entrypointContents": "serve router.php"}' > .googleconfig/app_start.json

# Copy over all application files
COPY . /srv/

# Run "composer install"
COPY --from=composer:1 /usr/bin/composer /usr/local/bin
RUN composer install --no-dev

# Set a runtime name and the function to use
ENV GAE_RUNTIME php73
ENV FUNCTION_TARGET translateString

# Start NGINX and PHP-FPM
ENTRYPOINT ["/start"]

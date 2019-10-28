FROM gcr.io/gae-runtimes/php72:php72_7_2_8_20181112_RC00

# Install gRPC and Composer
RUN curl https://getcomposer.org/download/1.7.2/composer.phar > /usr/local/bin/composer \
  && chmod +x /usr/local/bin/composer

# Enable PHP gRPC extension
RUN echo "extension=grpc.so" > /srv/grpc.ini

WORKDIR /app/

# Copy over Composer files and run "composer install"
COPY composer.* /app/
RUN composer install --no-dev

# Copy over all application files
COPY . /app/

# Run our "router.php" entrypoint to serve functions defined in index.php
ENTRYPOINT ["serve", "router.php"]

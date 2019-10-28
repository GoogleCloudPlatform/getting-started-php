FROM gcr.io/gae-runtimes/php72:php72_7_2_8_20180831_RC00

# Install gRPC and Composer
RUN apt-get update >/dev/null \
  && apt-get install -y autoconf build-essential libz-dev >/dev/null \
  && pecl install grpc >/dev/null \
  && curl https://getcomposer.org/download/1.7.2/composer.phar > /usr/local/bin/composer \
  && chmod +x /usr/local/bin/composer

WORKDIR /app/

# Enable PHP gRPC extension
RUN echo "extension=grpc.so" > /srv/grpc.ini

# Copy over Composer files and run composer install
COPY composer.* /app/
RUN composer install --no-dev

# Copy over all application files
COPY . /app/

ENTRYPOINT ["serve", "router.php"]

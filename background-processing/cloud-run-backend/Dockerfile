FROM gcr.io/gae-runtimes/php73:php73_20190827_7_3_7_RC03

WORKDIR /srv/

# Copy over all application files
COPY . .

# Run "composer install"
COPY --from=composer:1 /usr/bin/composer /usr/local/bin
RUN composer install --no-dev

# Set a runtime name and the function to use
ENV GAE_RUNTIME php73
ENV FUNCTION_TARGET translateString

# Start NGINX and PHP-FPM
# NOTE: You can configure the container to serve a different endpoint by
# creating a `.googleconfig/app_start.json` file with the contents
# {"entrypointContents": "some entrypoint"}
ENTRYPOINT ["/start"]

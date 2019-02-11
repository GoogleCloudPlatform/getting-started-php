# The Google App Engine php runtime is Debian Jessie with PHP installed
# and various os-level packages to allow installation of popular PHP
# libraries. The source is on github at:
#   https://github.com/GoogleCloudPlatform/php-docker
FROM gcr.io/google_appengine/php

# Add our NGINX and php.ini config
ENV DOCUMENT_ROOT=${APP_DIR}

# Replace these values with your own
ENV GOOGLE_APPLICATION_CREDENTIALS=/path/to/service-account-credentials.json
ENV CLOUDSQL_CONNECTION_NAME=YOUR_CLOUDSQL_CONNECTION_NAME
ENV CLOUDSQL_USER=YOUR_CLOUDSQL_USER
ENV CLOUDSQL_PASSWORD=YOUR_CLOUDSQL_PASSWORD

# Run MySQL proxy
RUN curl https://dl.google.com/cloudsql/cloud_sql_proxy.linux.amd64 -o /usr/local/bin/cloud_sql_proxy \
	&& chmod +x /usr/local/bin/cloud_sql_proxy
RUN cloud_sql_proxy -instances=$CLOUDSQL_CONNECTION_NAME=tcp:3306 &

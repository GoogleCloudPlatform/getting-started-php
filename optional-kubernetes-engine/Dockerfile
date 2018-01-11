# The Google App Engine php runtime is Debian Jessie with PHP installed
# and various os-level packages to allow installation of popular PHP
# libraries. The source is on github at:
#   https://github.com/GoogleCloudPlatform/php-docker
FROM gcr.io/google_appengine/php

# Add our NGINX and php.ini config
ENV DOCUMENT_ROOT=${APP_DIR}/web

# Workaround for AUFS-related permission issue:
# See https://github.com/docker/docker/issues/783#issuecomment-56013588
RUN cp -R ${APP_DIR} ${APP_DIR}-copy; rm -r ${APP_DIR}; mv ${APP_DIR}-copy ${APP_DIR}; chmod -R 550 ${APP_DIR}; chown -R root.www-data ${APP_DIR}

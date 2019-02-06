#!/bin/bash

# Copyright 2015 Google Inc.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

# Populate these values to use the CloudSQL backend
CLOUDSQL_CONNECTION_NAME="YOUR_CLOUDSQL_CONNECTION_NAME"
CLOUDSQL_USER="YOUR_CLOUDSQL_USER"
CLOUDSQL_PASSWORD="YOUR_CLOUDSQL_PASSWORD"
CLOUDSQL_DATABASE_NAME="" # optional: defaults to "getting_started"
CLOUDSQL_PORT=""          # optional: defaults to "3306"

# [START all]
set -e
export HOME=/root

# [START php]
# Install PHP and dependencies from apt
apt-get update
apt-get install -y git nginx php7.0 php7.0-fpm php7.0-mysql php7.0-dev php-pear pkg-config

# Install Composer
curl -sS https://getcomposer.org/installer | \
    /usr/bin/php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

# Get the application source code
gcloud source repos clone getting-started /opt/app

# Run Composer
composer install -d /opt/app --no-dev --no-ansi --no-progress
# [END php]

# [START cloud_sql]
# Install the Cloud SQL proxy so our application can connect to Cloud SQL
wget https://dl.google.com/cloudsql/cloud_sql_proxy.linux.amd64 -O /usr/local/bin/cloud_sql_proxy
chmod +x /usr/local/bin/cloud_sql_proxy

cloud_sql_proxy -instances=$CLOUDSQL_CONNECTION_NAME=tcp:3306 &
# [END cloud_sql]

# [START nginx]
# Disable the default NGINX configuration
rm /etc/nginx/sites-enabled/default

# Enable our NGINX configuration
cp /opt/app/gce_deployment/nginx/bookshelf.conf /etc/nginx/sites-available/bookshelf.conf
ln -s /etc/nginx/sites-available/bookshelf.conf /etc/nginx/sites-enabled/bookshelf.conf
cp /opt/app/gce_deployment/nginx/fastcgi_params /etc/nginx/fastcgi_params

# Replaces the variables inside fastcgi_env with configured variables above
cp /opt/app/gce_deployment/nginx/fastcgi_env /etc/nginx/fastcgi_env
sed -i -e "s/YOUR_CLOUDSQL_CONNECTION_NAME/$CLOUDSQL_CONNECTION_NAME/" /etc/nginx/fastcgi_env
sed -i -e "s/YOUR_CLOUDSQL_USER/$CLOUDSQL_USER/" /etc/nginx/fastcgi_env
sed -i -e "s/YOUR_CLOUDSQL_PASSWORD/$CLOUDSQL_PASSWORD/" /etc/nginx/fastcgi_env
sed -i -e "s/YOUR_CLOUDSQL_DATABASE_NAME/$CLOUDSQL_DATABASE_NAME/" /etc/nginx/fastcgi_env
sed -i -e "s/YOUR_CLOUDSQL_PORT/$CLOUDSQL_PORT/" /etc/nginx/fastcgi_env

# Start NGINX
systemctl restart nginx.service
# [END nginx]

# [START logging]
# Install Fluentd
curl -s "https://storage.googleapis.com/signals-agents/logging/google-fluentd-install.sh" | bash

# Enable our Fluentd configuration
cp /opt/app/gce_deployment/fluentd/bookshelf.conf /etc/google-fluentd/config.d/bookshelf.conf

# Start Fluentd
service google-fluentd restart &
# [END logging]
# [END all]

echo "Finished running startup-script.sh"

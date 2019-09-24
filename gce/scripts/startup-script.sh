#!/bin/bash

# Copyright 2019 Google LLC
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

# [START getting_started_script]
set -e
export HOME=/root

# Install PHP and dependencies from apt
apt-get update
apt-get install -y git nginx php7 php7-fpm php7-mysql php7-dev php-pear pkg-config

# Install Composer
curl -sS https://getcomposer.org/installer | \
    /usr/bin/php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

# Fetch the project ID from the Metadata server
PROJECT=$(curl -s "http://metadata.google.internal/computeMetadata/v1/project/project-id" -H "Metadata-Flavor: Google")

# Get the application source code
git config --global credential.helper gcloud.sh
git clone https://source.developers.google.com/p/$PROJECT /opt/src -b master
ln -s /opt/src/gce /opt/app

# Run Composer
composer install -d /opt/app --no-ansi --no-progress

# Disable the default NGINX configuration
rm /etc/nginx/sites-enabled/default

# Enable our NGINX configuration
cp /opt/app/gce/nginx/helloworld.conf /etc/nginx/sites-available/helloworld.conf
ln -s /etc/nginx/sites-available/helloworld.conf /etc/nginx/sites-enabled/helloworld.conf
cp /opt/app/gce/nginx/fastcgi_params /etc/nginx/fastcgi_params

# Start NGINX
systemctl restart nginx.service

# Install Fluentd
curl -s "https://storage.googleapis.com/signals-agents/logging/google-fluentd-install.sh" | bash

# Enable our Fluentd configuration
cp /opt/app/gce/fluentd/helloworld.conf /etc/google-fluentd/config.d/helloworld.conf

# Start Fluentd
service google-fluentd restart &
# [END getting_started_script]
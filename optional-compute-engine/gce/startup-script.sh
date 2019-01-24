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

# [START all]
set -e
export HOME=/root

# [START php]
# Install PHP and dependencies from apt
apt-get update
apt-get install -y git nginx mongodb-clients php5 php5-fpm php5-mysql php5-dev php-pear pkg-config
pecl install mongodb

# Enable the MongoDB PHP extension
echo "extension=mongodb.so" >> /etc/php5/mods-available/mongodb.ini
php5enmod mongodb

# Install Composer
curl -sS https://getcomposer.org/installer | \
    /usr/bin/php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

# Fetch the project ID from the Metadata server
PROJECTID=$(curl -s "http://metadata.google.internal/computeMetadata/v1/project/project-id" -H "Metadata-Flavor: Google")

# Get the application source code
git config --global credential.helper gcloud.sh
git clone https://source.developers.google.com/p/$PROJECTID /opt/src -b master
ln -s /opt/src/optional-compute-engine /opt/app

# Run Composer
composer install -d /opt/app --no-ansi --no-progress
# [END php]

# Decrypt the settings.yml file, if applicable
pushd /opt/app/config
if [-f settings.yml ]; then
  gcloud kms decrypt --location=global --keyring=[YOUR_KEY_RING] --key=[YOUR_KEY_NAME] --plaintext-file=settings.yml --ciphertext-file=settings.yml.enc
fi
popd

# [START project_config]
# Fetch the application config file from the Metadata server and add it to the project
curl -s "http://metadata.google.internal/computeMetadata/v1/instance/attributes/project-config" \
  -H "Metadata-Flavor: Google" >> /opt/app/config/settings.yml
# [END project_config]

# [START nginx]
# Disable the default NGINX configuration
rm /etc/nginx/sites-enabled/default

# Enable our NGINX configuration
cp /opt/app/gce/nginx/bookshelf.conf /etc/nginx/sites-available/bookshelf.conf
ln -s /etc/nginx/sites-available/bookshelf.conf /etc/nginx/sites-enabled/bookshelf.conf
cp /opt/app/gce/nginx/fastcgi_params /etc/nginx/fastcgi_params

# Start NGINX
systemctl restart nginx.service
# [END nginx]

# [START logging]
# Install Fluentd
curl -s "https://storage.googleapis.com/signals-agents/logging/google-fluentd-install.sh" | bash

# Enable our Fluentd configuration
cp /opt/app/gce/fluentd/bookshelf.conf /etc/google-fluentd/config.d/bookshelf.conf

# Start Fluentd
service google-fluentd restart &
# [END logging]
# [END all]

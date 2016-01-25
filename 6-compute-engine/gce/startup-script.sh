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

# Talk to the metadata server to get the project id
PROJECTID=$(curl -s "http://metadata.google.internal/computeMetadata/v1/project/project-id" -H "Metadata-Flavor: Google")

# [START logging]
curl -s "https://storage.googleapis.com/signals-agents/logging/google-fluentd-install.sh" | bash
cat >/etc/google-fluentd/config.d/bookshelf.conf << EOF
<source>
  type tail
  format none
  path /opt/app/log/*.log
  pos_file /var/tmp/fluentd.app.pos
  read_from_head true
  rotate_wait 10s
  tag bookshelf
</source>
EOF
service google-fluentd restart &
# [END logging]

# [START php]
# Install PHP and dependencies from apt
apt-get update
apt-get install -y git nginx mongodb-clients php5 php5-fpm php5-dev php-pear pkg-config
pecl install mongodb

# enable the pecl-installed mongodb extension
cat >/etc/php5/mods-available/20-mongodb.ini << EOF
extension=mongodb.so
EOF

ln -s /etc/php5/mods-available/20-mongodb.ini /etc/php5/fpm/conf.d/20-mongodb.ini
ln -s /etc/php5/mods-available/20-mongodb.ini /etc/php5/cli/conf.d/20-mongodb.ini

# Install composer
curl -sS https://getcomposer.org/installer | \
    /usr/bin/php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

# Get the source code
git config --global credential.helper gcloud.sh

# Change branch from master if not using master
git clone https://source.developers.google.com/p/$PROJECTID /opt/app -b master

# run composer
composer install -d /opt/app/bookshelf --no-ansi --no-progress
# [END php]

# [START project_config]
# pull our custom config file from the metadata server and add it to the project
curl -s "http://metadata.google.internal/computeMetadata/v1/instance/attributes/project-config" \
  -H "Metadata-Flavor: Google" >> /opt/app/bookshelf/config/settings.yml
# [END project_config]

# [START nginx]
# disable the default nginx configuration
rm /etc/nginx/sites-enabled/default

# enable our nginx configuration
cp /opt/app/bookshelf/gce/nginx/bookshelf.conf /etc/nginx/sites-available/bookshelf.conf
ln -s /etc/nginx/sites-available/bookshelf.conf /etc/nginx/sites-enabled/bookshelf.conf

# add fastcgi params for PHP
cp /opt/app/bookshelf/gce/nginx/fastcgi_params /etc/nginx/fastcgi_params

systemctl restart nginx.service
# [END nginx]
# [END all]

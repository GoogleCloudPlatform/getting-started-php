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

set -ev

MYDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

VARS=(
    CLOUDSDK_ACTIVE_CONFIG_NAME
    GOOGLE_PROJECT_ID
    GOOGLE_STORAGE_BUCKET
    GOOGLE_CREDENTIALS_BASE64
    TEST_BUILD_DIR
)

PREREQ="true"
for v in "${VARS[@]}"; do
    if [ -z "${!v}" ]; then
        echo "Please set ${v} envvar."
        PREREQ="false"
    fi
done

# Exit when any of the necessary envvar is not set.
if [ "${PREREQ}" = "false" ]; then
    exit 1
fi

# set github token to speed up composer if possible
if [ -n "$GH_TOKEN" ]; then
    composer config --global github-oauth.github.com ${GH_TOKEN};
fi;

cd "${TEST_BUILD_DIR}"

mkdir -p ${HOME}/bin

# Install php-cs-fixer
wget http://get.sensiolabs.org/php-cs-fixer.phar -O ${HOME}/bin/php-cs-fixer
chmod +x ${HOME}/bin/php-cs-fixer

# Install gcloud
if [ ! -d ${HOME}/gcloud/google-cloud-sdk ]; then
    mkdir -p ${HOME}/gcloud &&
    wget https://dl.google.com/dl/cloudsdk/release/google-cloud-sdk.tar.gz --directory-prefix=${HOME}/gcloud &&
    cd "${HOME}/gcloud" &&
    tar xzf google-cloud-sdk.tar.gz &&
    ./google-cloud-sdk/install.sh --usage-reporting false --path-update false --command-completion false &&
    cd "${TEST_BUILD_DIR}"
fi

# gcloud configurations
if [ ! -z $CLOUDSDK_ACTIVE_CONFIG_NAME ]; then
  gcloud config configurations create $CLOUDSDK_ACTIVE_CONFIG_NAME || true;
fi;
gcloud config set project ${GOOGLE_PROJECT_ID}
gcloud config set app/promote_by_default false

# Dump the credentials
php ${MYDIR}/dump_credentials.php

# Activate the service account
if [ -z "{$GOOGLE_APPLICATION_CREDENTIALS" ]; then
    echo "No service account key, skipping service account activation."
else
    gcloud auth activate-service-account --key-file \
        "${GOOGLE_APPLICATION_CREDENTIALS}"
fi

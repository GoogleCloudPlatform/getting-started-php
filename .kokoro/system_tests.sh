#!/bin/bash

# Copyright 2020 Google LLC
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#      http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

set -e

# Run code standards check when appropriate
if [ "${RUN_CS_CHECK}" = "true" ]; then
  wget http://cs.sensiolabs.org/download/php-cs-fixer-v2.phar -O php-cs-fixer
  chmod a+x php-cs-fixer
  ./php-cs-fixer fix --dry-run --diff
fi

# Kokoro directory for running these samples
cd github/getting-started-php

export GOOGLE_APPLICATION_CREDENTIALS=$KOKORO_GFILE_DIR/service-account.json
export GOOGLE_PROJECT_ID=$(cat "${GOOGLE_APPLICATION_CREDENTIALS}" | jq -r .project_id)
export GOOGLE_CLOUD_PROJECT=$GOOGLE_PROJECT_ID
export GOOGLE_VERSION_ID=$KOKORO_BUILD_NUMBER

# Activate the service account
if [ -f ${GOOGLE_APPLICATION_CREDENTIALS} ]; then
    gcloud auth activate-service-account \
        --key-file "${GOOGLE_APPLICATION_CREDENTIALS}" \
        --project $GOOGLE_CLOUD_PROJECT
fi

# Install composer in all directories containing composer.json
find . -name composer.json -not -path '*vendor/*' -exec dirname {} \; | while read COMPOSER_DIR
do
  pushd $COMPOSER_DIR;
  composer install --quiet;
  popd;
done

# Run the tests in each of the sample directories
find . -name 'phpunit*.xml*' -not -path '*vendor/*' | while read PHPUNIT_FILE
do
  pushd $(dirname $PHPUNIT_FILE);
  phpunit -v -c $(basename $PHPUNIT_FILE);
  popd;
done

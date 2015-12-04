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

# A script to run all the test locally.

MYDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source ${MYDIR}/variables.sh

# Coding style check.
php-cs-fixer fix --dry-run --diff --level=psr2 --fixers=concat_with_spaces .

# Run tests for each directories.
for DIR in "${DIRS[@]}"; do
    cd ${DIR}
    mkdir -p ${DIR}/build/logs
    vendor/bin/phpunit --coverage-clover ${DIR}/build/logs/clover.xml
done

cd ${TEST_BUILD_DIR}

mkdir -p ${TEST_BUILD_DIR}/build/logs

vendor/bin/coveralls -v --exclude-no-stmt

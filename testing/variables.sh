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

# Defines some variables

VARS=(
    CLOUDSDK_ACTIVE_CONFIG_NAME
    GOOGLE_PROJECT_ID
    GOOGLE_STORAGE_BUCKET
    MYSQL_CONNECTION_NAME
    MYSQL_DATABASE_NAME
    MYSQL_USER
    MYSQL_PASSWORD
    POSTGRES_CONNECTION_NAME
    POSTGRES_DATABASE_NAME
    POSTGRES_USER
    POSTGRES_PASSWORD
    GOOGLE_CREDENTIALS_BASE64
    TEST_BUILD_DIR
    FIREWALL_NAME
)

STEPS=(
    app-engine-flex/1-hello-world
    app-engine-flex/2-structured-data
    app-engine-flex/3-cloud-storage
    app-engine-flex/4-auth
    app-engine-flex/5-logging
    app-engine-flex/6-pubsub
    compute-engine
    kubernetes-engine
)

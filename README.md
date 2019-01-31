# Getting started with PHP on Google Cloud Platform

[![Build Status][travis-badge]][travis-link]

This repository contains the complete sample code for the
[PHP Getting Started on Google Cloud Platform][getting-started] tutorials.
Please refer to the tutorials for instructions on configuring, running, and
deploying these samples.

 - Configuring a Database with `Cloud SQL`\*\*
 - Uploading assets to `Cloud Storage`
 - Authenticating users with `Google Auth`
 - Logging app events with `Stackdriver Logging`
 - Using `Cloud Pub/Sub`

> \*\*App Engine Flex, GKE, and GCE tutorials also cover `Cloud Datastore` and `MongoDB`.

The code for each tutorial is in an individual folder in this repository:

Tutorial | Folder
---------|-------
[Deploying to App Engine Standard environment][app-engine-standard] | [app-engine-standard](app-engine-standard/)
[Deploying to Google Kubernetes Engine (GKE)][gke] | [kubernetes-engine](kubernetes-engine/)
[Deploying to Google Compute Engine (GCE)][gce] | [compute-engine](compute-engine/)
[Deploying to App Engine Flexible environment][gae-flex] | [app-engine-flex](app-engine-flex/)

**If you are unsure which App Engine environemnt to use, choose `App Engine
Standard`, as this runtime provides the best getting started experience.**

## Contributing changes

* See [CONTRIBUTING.md](CONTRIBUTING.md)

## Licensing

* See [LICENSE](LICENSE)

[travis-badge]: https://travis-ci.org/GoogleCloudPlatform/getting-started-php.svg?branch=master
[travis-link]: https://travis-ci.org/GoogleCloudPlatform/getting-started-php
[getting-started]: http://cloud.google.com/php/getting-started
[gae-standard]: http://cloud.google.com/php/getting-started/tutorial-app
[gke]: https://cloud.google.com/php/tutorials/bookshelf-on-container-engine
[gce]: https://cloud.google.com/php/tutorials/bookshelf-on-compute-engine
[gae-flex]: http://cloud.google.com/php/getting-started/tutorial-app


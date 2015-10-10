# Hello World for silex

# Prerequisites

- Install PHP 5.5 or greater
- Install Composer
- Install gcloud sdk
- cd into this directory

# Install dependency

To install dependency, do:

```sh
$ composer install
```

# Run locally

To run locally, do:

```sh
$ php -S localhost:8080 -t web
```

# Deploy

To deploy, do:

```sh
$ gcloud preview app deploy app.yaml
```

# Run tests

To run tests, do:

```sh
$ vendor/bin/phpunit -c tests/phpunit.xml
```

To run tests with coverage information, do:

```sh
$ vendor/bin/phpunit --coverage-text -c tests/phpunit.xml
```

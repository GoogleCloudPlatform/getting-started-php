# How to become a contributor and submit your own code

## Contributor License Agreements

We'd love to accept your patches! Before we can take them, we
have to jump a couple of legal hurdles.

Please fill out either the individual or corporate Contributor License Agreement
(CLA).

  * If you are an individual writing original source code and you're sure you
    own the intellectual property, then you'll need to sign an [individual CLA]
    (https://developers.google.com/open-source/cla/individual).
  * If you work for a company that wants to allow you to contribute your work,
    then you'll need to sign a [corporate CLA]
    (https://developers.google.com/open-source/cla/corporate).

Follow either of the two links above to access the appropriate CLA and
instructions for how to sign and return it. Once we receive it, we'll be able to
accept your pull requests.

## Contributing A Patch

1. Submit an issue describing your proposed change to the repo in question.
1. The repo owner will respond to your issue promptly.
1. If your proposed change is accepted, and you haven't already done so, sign a
   Contributor License Agreement (see details above).
1. Fork the desired repo, develop and test your code changes.
1. Ensure that your code adheres to the existing style in the sample to which
   you are contributing. Refer to the
   [Google Cloud Platform Samples Style Guide]
   (https://github.com/GoogleCloudPlatform/Template/wiki/style.html) for the
   recommended coding standards for this organization.
1. Ensure that your code has an appropriate set of unit tests which all pass.
1. Submit a pull request.

### Run the tests

Set up [application default credentials](https://cloud.google.com/docs/authentication/getting-started)
by setting the environment variable `GOOGLE_APPLICATION_CREDENTIALS` to the
path to a service account key JSON file and `GOOGLE_CLOUD_PROJECT` to your
Google Cloud project ID:

```
export GOOGLE_APPLICATION_CREDENTIALS=/path/to/your/credentials.json
export GOOGLE_CLOUD_PROJECT=YOUR_PROJECT_ID
```

These tests use `phpunit/phpunit:^7`. You can install this with composer
globally:

```
composer global require phpunit/phpunit:^7
```

Now you can run the tests in the samples directory!

```
cd $SAMPLES_DIRECTORY
phpunit
```

Use `phpunit -v` to get a more detailed output if there are errors.

## Style

Samples in this repository follow the [PSR2][psr2] and [PSR4][psr4]
recommendations. This is enforced using [PHP CS Fixer][php-cs-fixer].

Install that by running

```
composer global require friendsofphp/php-cs-fixer
```

Then to fix your directory or file run

```
php-cs-fixer fix .
php-cs-fixer fix path/to/file
```

[psr2]: http://www.php-fig.org/psr/psr-2/
[psr4]: http://www.php-fig.org/psr/psr-4/
[php-cs-fixer]: https://github.com/FriendsOfPHP/PHP-CS-Fixer

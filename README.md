# civicrm-asset-plugin

The `civicrm-asset-plugin` is a [composer](https://getcomposer.org/) plugin
to support project structures, such as the common Drupal 8 templates, in
which PHP-code (`*.php`) and web-assets (`*.js`, `*.css`, etc) are split
apart.  It's job is it identify web-assets related to CiviCRM and copy them
to another folder.

It supports assets from:

* `civicrm-core`
* `civicrm-packages`
* Any extension containing an `info.xml` file

## Example

Suppose you have the following source tree:

```
vendor/
  civicrm/
    civicrm-core
    civicrm-packages
  civipkg/
    org.civicrm.api4
    org.civicrm.flexmailer
    org.civicrm.shoreditch
    uk.co.vedaconsulting.mosaico
```

By default, the assets from these folders would be published to:

```
web/
  libraries/
    civicrm/
      core
      packages
      org.civicrm.api4
      org.civicrm.flexmailer
      org.civicrm.shoreditch
      uk.co.vedaconsulting.mosaico
```

## Options

In the root `composer.json`,  the site administrator may customize some
options:

```json
"extra": {
  "civicrm-asset": {
    ## Local file path of the public/web-readable folder
    "path": "web/libraries/civicrm"

    ## Public URL of the public/web-readable folder
    "url": "/libraries/civicrm"

    ## Customize default list of assets
    "assets:*": {
      "include": ["**.js", "**.css"],
      "exclude-dir": [".git"]
    },

    ## Customize the specific list of assets for "web/libraries/civicrm/core"
    ## by replacing the inclusion-list and exclusion-list.
    "assets:core": {
      "include": ["js/**", "css/**", "ang/**", "templates/**.png", "templates/**.jpg"],
      "exclude-dir": [".git", "/CRM"]
    },

    ## Customize the specific list of assets for "web/libraries/civicrm/packages"
    ## by adding to the inclusion-list and exclusion-list.
    "assets:packages": {
      "+include": ["**.jpeg"],
      "+exclude-dir": ["_ORIGINAL_"]
    }
  }
}
```

If you do not set these explicitly, then some defaults come into play. The
defaults are calculated based on other aspects of the project.

```js
// Style: `drupal-composer/drupal-project`
// Trigger: The `installer-paths` has a `drupal-library` or `drupal-core` mapping which uses `web/`
{
  "path": "web/libraries/civicrm",
  "url": "/libraries/civicrm"
}

// Style: Drupal 8 Tarball / Drush Dl
// Trigger: The `installer-paths` has a `drupal-library` or `drupal-core` mapping which does NOT use `web/`
{
  "path": "libraries/civicrm",
  "url": "/libraries/civicrm"
}

// Style: Other
// Trigger: No better default detected
{
  "path": "civicrm-assets"
  "url": "/civicrm-assets"
}
```

## Automated Tests

The `tests/` folder includes integration tests written with PHPUnit.  Each
integration-test generates a new folder/project with a plausible,
representative `composer.json` file and executes `composer install`. It
checks the output has the expected files.

To run the tests, you will need `composer` and `phpunit` in the `PATH`.

```
[~/src/civicrm-asset-plugin] which composer
/Users/totten/bknix/civicrm-buildkit/bin/composer

[~/src/civicrm-asset-plugin] which phpunit
/Users/totten/bknix/civicrm-buildkit/bin/phpunit

[~/src/civicrm-asset-plugin] phpunit
PHPUnit 5.7.27 by Sebastian Bergmann and contributors.

.....                                                               5 / 5 (100%)

Time: 40.35 seconds, Memory: 10.00MB

OK (5 tests, 7 assertions)
```

The integration tests can be a bit large/slow. To monitor the tests more
closesly, set the `DEBUG` variable, as in:

```
[~/src/civicrm-asset-plugin] env DEBUG=2 phpunit
```

## Local Dev Harness

What if you want to produce an environment which uses the current plugin
code - one where you can quickly re-run `composer` commands while
iterating on code?

You may use any of the integration-tests to initialize a baseline
environment:

```
env USE_TEST_PROJECT=$HOME/src/myprj DEBUG=2 phpunit tests/Integration/DrupalProjectPathsTest.php
```

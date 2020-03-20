# civicrm/civicrm-asset-plugin

The `civicrm/civicrm-asset-plugin` is a [composer](https://getcomposer.org/)
plugin to support projects in which PHP files (`*.php`) and web-assets
(`*.js`, `*.css`, etc) must be split apart.  Its job is to identify
web-assets related to CiviCRM, copy them to another folder, and configure
CiviCRM to use that folder.

It reads assets from these composer packages:

* `civicrm/civicrm-core`
* `civicrm/civicrm-packages`
* Any `composer` package with a CiviCRM extension (`info.xml`) in the root

Assets are synchronized to a public folder.  If your web-site matches a
well-known project template (e.g.  `drupal/recommended-project` or
`drupal/legacy-project` in D8.8+), then the plugin will detect the output folder
automatically. Otherwise, it can be configured.

## Quick start

Simply add the package `civicrm/civicrm-asset-plugin` to your project:

```bash
composer require civicrm/civicrm-asset-plugin:~1.0
```

For a well-known project template, nothing more is required.

For a novel or unrecognized project structure, you should explicitly
configure the sync mechanism.  Edit `composer.json` and describe where
assets should go, e.g.:

```js
"extra": {
  "civicrm-asset": {
    "path": "web/libraries/civicrm",
    "url": "/libraries/civicrm"
  }
}
```

After editing the options, republish with:

```bash
composer civicrm:publish
```

The remainder of this document presents a fuller explanation of the
mechanics and options.

## Example

Suppose you have the following source tree:

```
web/
  index.php
vendor/
  civicrm/
    civicrm-core/
    civicrm-packages/
  civipkg/
    org.civicrm.api4/
    org.civicrm.flexmailer/
    org.civicrm.shoreditch/
    uk.co.vedaconsulting.mosaico/
```

The plugin will copy Civi-related assets from `vendor/` to `web/`.

```
web/
  libraries/
    civicrm/
      core/
      packages/
      org.civicrm.api4/
      org.civicrm.flexmailer/
      org.civicrm.shoreditch/
      uk.co.vedaconsulting.mosaico/
```

## Options

In the root `composer.json`,  the site administrator may customize some
options:

```js
"extra": {
  "civicrm-asset": {
    // Local file path of the public/web-readable folder
    "path": "web/libraries/civicrm",

    // Public URL of the public/web-readable folder
    "url": "/libraries/civicrm",

    // How to put the file in the public/web-readable folder?
    //
    // TIP: For a safe+portable default, use "copy". On a local-dev machine, set environment variable
    // `CIVICRM_COMPOSER_ASSET=symdir` so that all local builds use "symdir".
    //
    // Options:
    //  - "copy": Each asset/file is individually copied.
    //  - "symlink": Each asset/file is individually symlinked.
    //  - "symdir": The main directories are symlinked, bringing all files underneath.
    //
    //                                                   "copy"        "symlink"       "symdir"
    //                                                   ------        ---------       --------
    // Compatiblity: Windows                             Good          Poor            Poor
    // Compatiblity: Linux/OSX                           Good          Good            Good
    // Compatiblity: HTTPD                               Good          Depends         Depends
    // Precision: Does it *only* publish assets?         Good          Good            Poor
    // Updating: Do code-edits require manual sync?      Manual        Automatic       Automatic
    // Updating: Do new-files require manual sync?       Manual        Manual          Automatic
    //
    "file-mode": "copy",

    // Customize default list of assets
    "assets:*": {
      "include": ["**.js", "**.css"],
      "exclude-dir": [".git"]
    },

    // Customize the specific list of assets for "web/libraries/civicrm/core"
    // by replacing the inclusion-list and exclusion-list.
    "assets:core": {
      "include": ["js/**", "css/**", "ang/**", "templates/**.png", "templates/**.jpg"],
      "exclude-dir": [".git", "/CRM"]
    },

    // Customize the specific list of assets for "web/libraries/civicrm/packages"
    // by adding to the inclusion-list and exclusion-list.
    "assets:packages": {
      "+include": ["**.jpeg"],
      "+exclude-dir": ["_ORIGINAL_"]
    }
  }
}
```

If you do not set these explicitly, then some defaults come into play.

The defaults are tuned for a few different use-cases.

```js
// Use-Case: `drupal-composer/drupal-project`
// Use-Case: `drupal/recommended-project`
// Rule: If the `installer-paths` has a `drupal-library` or `drupal-core` mapping which uses `web/`, then default to:
{
  "path": "web/libraries/civicrm",
  "url": "/libraries/civicrm"
}

// Use-Case: Drupal 8 Tarball / Drush Dl
// Use-Case: `drupal/legacy-project`
// Rule: If the `installer-paths` has a `drupal-library` or `drupal-core` mapping which does NOT use `web/`, then default to:
{
  "path": "libraries/civicrm",
  "url": "/libraries/civicrm"
}

// Use-Case: Other/Unknown
// Rule: If no other defaults apply, then the defaults are:
{
  "path": "civicrm-assets"
  "url": "/civicrm-assets"
}
```

The full heuristics are enumerated in `src/PublisherDefaults.php`.

## Generated asset map and global variables

At runtime, CiviCRM needs some information about how to load assets/files provided by
`composer` packages (`civicrm/civicrm-core`, `civicrm/civicrm-packages`, and any extensions).
This plugin generates a map and puts it into a few global variables:

* `$civicrm_setting`: Defines certain path/URL-related settings such as `userFrameworkResourceURL`.
* `$civicrm_paths`: Defines the paths and URLs for `[civicrm.core]` and `[civicrm.packages]`.
* `$civicrm_asset_map`: For each composer package with sync'd assets, this identifies:
    * `src`: Local path where assets are read from
    * `dest`: Local path where assets are sync'd to
    * `url`: Configured URL for the `dest`

At runtime, the global variables will be loaded automatically (via `vendor/composer/autoload.php`).

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

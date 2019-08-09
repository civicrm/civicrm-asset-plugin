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

## Testing

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

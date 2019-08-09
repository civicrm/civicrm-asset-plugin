# civicrm-asset-plugin

## Testing

The `tests/` folder includes integration tests written with PHPUnit. To run
the tests:

* Ensure that `composer` and `phpunit` (v5+) are installed in your PATH.
* Simply run `phpunit5`

The integration tests will initialize new projects with different variations
of `composer.json`; in each project, they'll run `composer install` (etc)
and check the resulting build.

To monitor the subcommands used by the tests, set the `DEBUG` variable, as in:

```bash
env DEBUG=2 phpunit
```

<?php
namespace Civi\AssetPlugin;

use ProcessHelper\ProcessHelper as PH;

class DrupalProjectPathsTest extends \Civi\AssetPlugin\AssetPluginTestCase {

  public static function getComposerJson() {
    return [
      'name' => 'test/default-paths',
      'authors' => [
        [
          'name' => 'Tester McFakus',
          'email' => 'tester@example.org',
        ],
      ],
      'require' => [
        'composer/installers' => '^1.2',
        'cweagans/composer-patches' => '^1.6.5',
        'drupal-composer/drupal-scaffold' => '^2.5',
        'drupal/console' => '^1.0.2',
        'drupal/core' => '^8.7.0',
        'drush/drush' => '^9.0.0',
        'vlucas/phpdotenv' => '^2.4',
        'webflo/drupal-finder' => '^1.0.0',
        'webmozart/path-util' => '^2.3',
        'zaporylie/composer-drupal-optimizations' => '^1.0',

        'civicrm/civicrm-asset-plugin' => '@dev',
        'civicrm/civicrm-core' => '5.16.x-dev',
        'civicrm/civicrm-packages' => '5.16.x-dev',
      ],
      'repositories' => [
        'src' => [
          'type' => 'path',
          'url' => self::getPluginSourceDir(),
        ],
        'drupal' => [
          'type' => 'composer',
          'url' => 'https://packages.drupal.org/8',
        ],
      ],
      'minimum-stability' => 'dev',
      'extra' => [
        'installer-paths' => [
          'web/core' => ['type:drupal-core'],
          'web/libraries/{$name}' => ['type:drupal-library'],
          'web/modules/contrib/{$name}' => ['type:drupal-module'],
          'web/profiles/contrib/{$name}' => ['type:drupal-profile'],
          'web/themes/contrib/{$name}' => ['type:drupal-theme'],
          'drush/Commands/{$name}' => ['type:drupal-drush'],
        ],
      ],
    ];
  }

  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    self::initTestProject(static::getComposerJson());
    PH::runOk('composer install');
  }

  public function testCivicrmCss() {
    // Source file:
    $this->assertFileExists('vendor/civicrm/civicrm-core/css/civicrm.css');

    // Target file:
    // FIXME $this->assertFileExists('web/libraries/civicrm/core/css/civicrm.css');

    // FIXME $this->assertEquals(...content...);
    $this->markTestIncomplete('Not implemented');
  }

  public function testPackagesPhp() {
    $this->assertFileExists('vendor/civicrm/civicrm-packages/HTML/QuickForm.php');
    $this->assertFileNotExists('web/libraries/civicrm/packages/HTML/QuickForm.php');
  }

  public function testAutoloadCivicrmPaths() {
    $proc = PH::runOk(['php -r @CODE', 'CODE' => 'require_once "vendor/autoload.php"; echo json_encode($GLOBALS["civicrm_paths"], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);']);
    $paths = json_decode($proc->getOutput(), 1);

    $expectPaths = [];
    $expectPaths['civicrm.root']['path'] = realpath(self::getTestDir()) . '/web/libraries/civicrm/core';
    $expectPaths['civicrm.root']['url'] = 'FIXME/libraries/civicrm/core';
    $expectPaths['civicrm.packages']['path'] = realpath(self::getTestDir()) . '/web/libraries/civicrm/packages';
    $expectPaths['civicrm.packages']['url'] = 'FIXME/libraries/civicrm/packages';
    // FIXME url checks

    $count = 0;
    foreach ($expectPaths as $pathVar => $variants) {
      foreach ($variants as $variant => $expectPathValue) {
        $this->assertNotEmpty(($expectPathValue));
        // FIXME $this->assertTrue(file_exists($expectPathValue));
        // FIXME $this->assertTrue(file_exists($realActualPathValue));
        $this->assertEquals($expectPathValue, $paths[$pathVar][$variant],
          "Expect paths[$pathVar][$variant] to match");
        $count++;
      }
    }
    $this->assertEquals(4, $count);
  }

}

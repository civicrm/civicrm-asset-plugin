<?php
namespace Civi\AssetPlugin\Integration;

use ProcessHelper\ProcessHelper as PH;

/**
 * Class DrupalProjectPathsTest
 * @package Civi\AssetPlugin\Integration
 *
 * In this case, we follow the default project structure from
 * 'drupal-composer/drupal-project' and simply add 'civicrm-{core,asset-plugin}`
 * as requirements.
 *
 * The default paths are determined automatically from the Drupal config.
 */
class DrupalProjectPathsTest extends \Civi\AssetPlugin\Integration\IntegrationTestCase {

  public static function getComposerJson() {
    return parent::getComposerJson() + [
      'name' => 'test/drupal-paths',
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
        'civicrm/civicrm-core' => '@stable',
        'civicrm/civicrm-packages' => '@stable',
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
    PH::runOk('COMPOSER_MEMORY_LIMIT=-1 composer install');
    // PH::runOk('composer civicrm:publish');
  }

  public function testCivicrmCss() {
    $this->assertFileExists('vendor/civicrm/civicrm-core/css/civicrm.css');
    $this->assertFileExists('web/libraries/civicrm/core/css/civicrm.css');
    $this->assertSameFileContent(
      'vendor/civicrm/civicrm-core/css/civicrm.css',
      'web/libraries/civicrm/core/css/civicrm.css',
      'Input and output files should have the same content'
    );
  }

  public function testPackagesPhp() {
    $this->assertFileExists('vendor/civicrm/civicrm-packages/HTML/QuickForm.php');
    $this->assertFileNotExists('web/libraries/civicrm/packages/HTML/QuickForm.php');
  }

  public function testAutoloadCivicrmPaths() {
    $proc = PH::runOk(['php -r @CODE', 'CODE' => 'require_once "vendor/autoload.php"; echo json_encode($GLOBALS["civicrm_paths"], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);']);
    $actualPaths = json_decode($proc->getOutput(), 1);

    // The JS/CSS assets are sync'd to web dir, but the $civicrm_paths should still autoload PHP from the src folder.
    $expectPaths = [];
    $expectPaths['civicrm.root']['path'] = realpath(self::getTestDir()) . '/vendor/civicrm/civicrm-core/';
    $expectPaths['civicrm.root']['url'] = '/libraries/civicrm/core/';
    $expectPaths['civicrm.packages']['path'] = realpath(self::getTestDir()) . '/vendor/civicrm/civicrm-packages/';
    $expectPaths['civicrm.packages']['url'] = '/libraries/civicrm/packages/';
    // FIXME url checks

    $count = 0;
    foreach ($expectPaths as $pathVar => $expectValues) {
      $this->assertNotEmpty($expectValues['path']);
      $this->assertNotEmpty($expectValues['url']);
      $this->assertTrue(file_exists($expectValues['path']));
      $this->assertEquals($expectValues['path'], $actualPaths[$pathVar]['path'], "Expect paths[$pathVar][path] to match");
      $this->assertEquals($expectValues['url'], $actualPaths[$pathVar]['url'], "Expect paths[$pathVar][url] to match");
      $count++;
    }
    $this->assertEquals(2, $count);
  }

}

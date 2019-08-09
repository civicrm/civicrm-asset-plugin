<?php
namespace Civi\AssetPlugin\Basic;

use ProcessHelper\ProcessHelper as PH;

class CustomPathsTest extends \Civi\AssetPlugin\AssetPluginTestCase {

  public static function getComposerJson() {
    return [
      'name' => 'test/custom-paths',
      'authors' => [
        [
          'name' => 'Tester McFakus',
          'email' => 'tester@example.org',
        ],
      ],
      'require' => [
        'civicrm/civicrm-asset-plugin' => '@dev',
        'civicrm/civicrm-core' => '5.16.x-dev',
        'civicrm/civicrm-packages' => '5.16.x-dev',
        'civipkg/org.civicrm.api4' => '4.4.3',
      ],
      'repositories' => [
        'src' => [
          'type' => 'path',
          'url' => self::getPluginSourceDir(),
        ],
        'api4' => [
          'type' => 'package',
          'package' => [
            'name' => 'civipkg/org.civicrm.api4',
            'version' => '4.4.3',
            'dist' => [
              'url' => 'https://github.com/civicrm/org.civicrm.api4/archive/4.4.2.zip',
              'type' => 'zip',
            ],
          ],
        ],
      ],
      'minimum-stability' => 'dev',
      'extras' => [
        'civicrm-asset' => [
          'local' => 'htdocs/my-assets',
          'url' => '/wonky-assets',
        ],
      ],
    ];
  }

  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    self::initTestProject(static::getComposerJson());
    PH::runOk('composer install');
  }

  public function testHasCivicrmCss() {
    $this->assertFileExists('vendor/civicrm/civicrm-core/css/civicrm.css');
    // $this->assertFileExists('web/libraries/civicrm/civicrm-core/css/civicrm.css');
    // $this->assertEquals();
  }

  public function testPackagesPhp() {
    $this->assertFileExists('vendor/civicrm/civicrm-packages/HTML/QuickForm.php');
    $this->assertFileNotExists('web/libraries/civicrm-packages/HTML/QuickForm.php');
  }

}

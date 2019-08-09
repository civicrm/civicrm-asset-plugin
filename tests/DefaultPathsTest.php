<?php
namespace Civi\AssetPlugin\Basic;

use ProcessHelper\ProcessHelper as PH;

class DefaultPathsTest extends \Civi\AssetPlugin\AssetPluginTestCase {

  public static function getComposerJson() {
    return [
      "name" => "test/default-paths",
      "authors" => [
        [
          "name" => "Tester McFakus",
          "email" => "tester@example.org",
        ],
      ],
      "require" => [
        "civicrm/civicrm-asset-plugin" => "@dev",
        "civicrm/civicrm-core" => "5.16.x-dev",
        "civicrm/civicrm-packages" => "5.16.x-dev",
      ],
      "repositories" => [
        "src" => [
          "type" => "path",
          "url" => self::getPluginSourceDir(),
        ],
      ],
      "minimum-stability" => "dev",
    ];
  }

  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    self::initTestProject(static::getComposerJson());
    PH::runOk('composer install');
  }

  public function testHasCivicrmCss() {
    $this->assertFileExists('vendor/civicrm/civicrm-core/css/civicrm.css');
    // $this->assertFileExists('web/libraries/civicrm-core/css/civicrm.css');
    // $this->assertEquals();
  }

  public function testPackagesPhp() {
    $this->assertFileExists('vendor/civicrm/civicrm-packages/HTML/QuickForm.php');
    $this->assertFileNotExists('web/libraries/civicrm-packages/HTML/QuickForm.php');
  }

}

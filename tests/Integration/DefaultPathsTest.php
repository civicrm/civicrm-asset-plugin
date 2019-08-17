<?php
namespace Civi\AssetPlugin\Integration;

use ProcessHelper\ProcessHelper as PH;

class DefaultPathsTest extends \Civi\AssetPlugin\Integration\IntegrationTestCase {

  public static function getComposerJson() {
    return parent::getComposerJson() + [
      'name' => 'test/default-paths',
      'require' => [
        'civicrm/civicrm-asset-plugin' => '@dev',
        'civicrm/civicrm-core' => '5.16.x-dev',
        'civicrm/civicrm-packages' => '5.16.x-dev',
        'civipkg/org.civicrm.api4' => '4.4.3',
      ],
      'minimum-stability' => 'dev',
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

  public function testApi4Assets() {
    // Source file:
    $this->assertFileExists('vendor/civipkg/org.civicrm.api4/images/ApiExplorer.png');

    // Target file:
    // FIXME $this->assertFileExists('web/libraries/civipkg/org.civicrm.api4/images/ApiExplorer.png');

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
    $expectPaths['civicrm.root']['path'] = realpath(self::getTestDir()) . '/civicrm-assets/core';
    $expectPaths['civicrm.root']['url'] = 'FIXME/civicrm-assets/core';
    $expectPaths['civicrm.packages']['path'] = realpath(self::getTestDir()) . '/civicrm-assets/packages';
    $expectPaths['civicrm.packages']['url'] = 'FIXME/civicrm-assets/packages';
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

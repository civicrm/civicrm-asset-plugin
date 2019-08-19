<?php
namespace Civi\AssetPlugin\Integration;

use ProcessHelper\ProcessHelper as PH;

class DefaultPathsTest extends \Civi\AssetPlugin\Integration\IntegrationTestCase {

  public static function getComposerJson() {
    return parent::getComposerJson() + [
      'name' => 'test/default-paths',
      'require' => [
        'civicrm/civicrm-asset-plugin' => '@dev',
        'civicrm/civicrm-core' => 'dev-master-composer-all-the-things',
        'civicrm/civicrm-packages' => 'dev-master',
        'civipkg/org.civicrm.api4' => '4.4.3',
      ],
      'minimum-stability' => 'dev',
    ];
  }

  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    self::initTestProject(static::getComposerJson());
    PH::runOk('composer install');
    // PH::runOk('composer civicrm:publish');
  }

  public function testCivicrmCss() {
    $this->assertFileExists('vendor/civicrm/civicrm-core/css/civicrm.css');
    $this->assertFileExists('civicrm-assets/core/css/civicrm.css');
    $this->assertEquals(
      file_get_contents('vendor/civicrm/civicrm-core/css/civicrm.css'),
      file_get_contents('civicrm-assets/core/css/civicrm.css'),
      'Input and output files should have the same content'
    );
  }

  public function testApi4Assets() {
    $this->assertFileExists('vendor/civipkg/org.civicrm.api4/images/ApiExplorer.png');
    $this->assertFileExists('civicrm-assets/org.civicrm.api4/images/ApiExplorer.png');
    $this->assertSameFileContent(
      'vendor/civipkg/org.civicrm.api4/images/ApiExplorer.png',
      'civicrm-assets/org.civicrm.api4/images/ApiExplorer.png',
      'Input and output files should have the same content'
    );
  }

  public function testPackagesPhp() {
    $this->assertFileExists('vendor/civicrm/civicrm-packages/HTML/QuickForm.php');
    $this->assertFileNotExists('civicrm-assets/packages/HTML/QuickForm.php');
  }

  public function testAutoloadCivicrmPaths() {
    $proc = PH::runOk(['php -r @CODE', 'CODE' => 'require_once "vendor/autoload.php"; echo json_encode($GLOBALS["civicrm_paths"], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);']);
    $actualPaths = json_decode($proc->getOutput(), 1);

    $expectPaths = [];
    $expectPaths['civicrm.root']['path'] = realpath(self::getTestDir()) . '/civicrm-assets/core';
    $expectPaths['civicrm.root']['url'] = 'FIXME/civicrm-assets/core';
    $expectPaths['civicrm.packages']['path'] = realpath(self::getTestDir()) . '/civicrm-assets/packages';
    $expectPaths['civicrm.packages']['url'] = 'FIXME/civicrm-assets/packages';
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

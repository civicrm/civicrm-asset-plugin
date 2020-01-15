<?php
namespace Civi\AssetPlugin\Integration;

use ProcessHelper\ProcessHelper as PH;

class CustomPathsTest extends \Civi\AssetPlugin\Integration\IntegrationTestCase {

  public static function getComposerJson() {
    return parent::getComposerJson() + [
      'name' => 'test/custom-paths',
      'require' => [
        'civicrm/civicrm-asset-plugin' => '@dev',
        'civicrm/civicrm-core' => '@stable',
        'civicrm/civicrm-packages' => '@stable',
        'civipkg/org.civicrm.api4' => '4.4.3',
      ],
      'minimum-stability' => 'dev',
      'extra' => [
        'civicrm-asset' => [
          'path' => 'htdocs/foo-civi-assets',
          'url' => 'http://example.com/bar-civi-assets',
          // FIXME: Maybe custom 'files' listing as well?
        ],
      ],
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
    $this->assertFileExists('htdocs/foo-civi-assets/core/css/civicrm.css');
    $this->assertEquals(
      file_get_contents('vendor/civicrm/civicrm-core/css/civicrm.css'),
      file_get_contents('htdocs/foo-civi-assets/core/css/civicrm.css'),
      'Input and output files should have the same content'
    );
  }

  public function testApi4Assets() {
    $this->assertFileExists('vendor/civipkg/org.civicrm.api4/images/ApiExplorer.png');
    $this->assertFileExists('htdocs/foo-civi-assets/org.civicrm.api4/images/ApiExplorer.png');
    $this->assertSameFileContent(
      'vendor/civipkg/org.civicrm.api4/images/ApiExplorer.png',
      'htdocs/foo-civi-assets/org.civicrm.api4/images/ApiExplorer.png',
      'Input and output files should have the same content'
    );
  }

  public function testPackagesPhp() {
    $this->assertFileExists('vendor/civicrm/civicrm-packages/HTML/QuickForm.php');
    $this->assertFileNotExists('htdocs/foo-civi-assets/packages/HTML/QuickForm.php');
  }

  public function testAutoloadCivicrmPaths() {
    $proc = PH::runOk([
      0 => 'php -r @CODE',
      'CODE' => 'require_once "vendor/autoload.php"; echo json_encode($GLOBALS["civicrm_paths"], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);',
    ]);
    $actualPaths = json_decode($proc->getOutput(), 1);

    // The JS/CSS assets are sync'd to web dir, but the $civicrm_paths should still autoload PHP from the src folder.
    $expectPaths = [];
    $expectPaths['civicrm.root']['path'] = realpath(self::getTestDir()) . '/vendor/civicrm/civicrm-core/';
    $expectPaths['civicrm.root']['url'] = 'http://example.com/bar-civi-assets/core/';
    $expectPaths['civicrm.packages']['path'] = realpath(self::getTestDir()) . '/vendor/civicrm/civicrm-packages/';
    $expectPaths['civicrm.packages']['url'] = 'http://example.com/bar-civi-assets/packages/';
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

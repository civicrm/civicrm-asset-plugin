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
          'path' => 'htdocs/foo-civi-assets',
          'url' => '/bar-civi-assets',
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
    $this->assertFileExists('vendor/civicrm/civicrm-core/css/civicrm.css');
    // FIXME $this->assertFileExists('htdocs/foo-civi-assets/core/css/civicrm.css');
    // FIXME $this->assertEquals(...content...);
    $this->markTestIncomplete('Not implemented');
  }

  public function testApi4Assets() {
    $this->assertFileExists('vendor/civipkg/org.civicrm.api4/images/ApiExplorer.png');
    // FIXME $this->assertFileExists('htdocs/foo-civi-assets/org.civicrm.api4/images/ApiExplorer.png');
    // FIXME $this->assertEquals(...content...);
    $this->markTestIncomplete('Not implemented');
  }

  public function testPackagesPhp() {
    $this->assertFileExists('vendor/civicrm/civicrm-packages/HTML/QuickForm.php');
    $this->assertFileNotExists('htdocs/foo-civi-assets/packages/HTML/QuickForm.php');
  }

  public function testAutoloadCivicrmPaths() {
    $proc = PH::runOk([
      'php -r @CODE',
      'CODE' => 'require_once "vendor/autoload.php"; echo json_encode($GLOBALS["civicrm_paths"], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);',
    ]);
    $paths = json_decode($proc->getOutput(), 1);

    $expectPaths = [];
    $expectPaths['civicrm.root']['path'] = self::getTestDir() . '/htdocs/foo-civi-assets/core';
    $expectPaths['civicrm.packages']['path'] = self::getTestDir() . '/htdocs/foo-civi-assets/packages';
    // FIXME url checks

    $count = 0;
    foreach ($expectPaths as $pathVar => $variants) {
      foreach ($variants as $variant => $expectPathValue) {
        $realExpectPathValue = realpath($expectPathValue);
        $realActualPathValue = realpath($paths[$pathVar][$variant]);
        $this->assertNotEmpty($realExpectPathValue);
        $this->assertTrue(file_exists($expectPathValue));
        $this->assertTrue(file_exists($realActualPathValue));
        $this->assertEquals(realpath($expectPathValue), realpath($paths[$pathVar][$variant]),
          "Expect paths[$pathVar][$variant] to match");
        $count++;
      }
    }
    $this->assertEquals(count($expectPaths), $count);
  }

}

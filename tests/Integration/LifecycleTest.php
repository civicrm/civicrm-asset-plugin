<?php

namespace Civi\AssetPlugin\Integration;

use ProcessHelper\ProcessHelper as PH;

class LifecycleTest extends IntegrationTestCase {

  public static function getComposerJson() {
    return parent::getComposerJson() + [
      'name' => 'test/lifecycle-test',
      'require' => [
        'civicrm/civicrm-asset-plugin' => '@dev',
        'civicrm/civicrm-core' => '@stable',
        'civicrm/civicrm-packages' => '@stable',
      ],
      'minimum-stability' => 'dev',
      'extra' => [
        'civicrm-asset' => [
          'path' => 'web/libraries/civicrm',
          'url' => '/libraries/civicrm',
          'mode' => 'copy',
          // FIXME: Maybe custom 'files' listing as well?
        ],
      ],
    ];
  }

  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    self::initTestProject(static::getComposerJson());
    PH::runOk('composer install');
    // PH::runOk('composer civicrm:publish -D');
  }

  /**
   * As the local admins cycles through the different publication modes,
   * the content should change.
   */
  public function testFileModes() {
    // Start out in 'copy' mode
    $this->assertDirIsNormal('web/libraries/civicrm/core');
    $this->assertFileIsNormal('vendor/civicrm/civicrm-core/css/civicrm.css');
    $this->assertFileIsNormal('web/libraries/civicrm/core/css/civicrm.css');
    $this->assertSameFileContent('vendor/civicrm/civicrm-core/css/civicrm.css', 'web/libraries/civicrm/core/css/civicrm.css');

    PH::runOk('env CIVICRM_COMPOSER_ASSET=symlink composer civicrm:publish');
    $this->assertDirIsNormal('web/libraries/civicrm/core');
    $this->assertFileIsNormal('vendor/civicrm/civicrm-core/css/civicrm.css');
    $this->assertFileIsSymlink('web/libraries/civicrm/core/css/civicrm.css');
    $this->assertSameFileContent('vendor/civicrm/civicrm-core/css/civicrm.css', 'web/libraries/civicrm/core/css/civicrm.css');

    PH::runOk('env CIVICRM_COMPOSER_ASSET=symdir composer civicrm:publish');
    $this->assertDirIsSymlink('web/libraries/civicrm/core');
    $this->assertFileIsNormal('vendor/civicrm/civicrm-core/css/civicrm.css');
    $this->assertFileIsNormal('web/libraries/civicrm/core/css/civicrm.css');
    $this->assertSameFileContent('vendor/civicrm/civicrm-core/css/civicrm.css', 'web/libraries/civicrm/core/css/civicrm.css');

    PH::runOk('composer civicrm:publish');
    $this->assertDirIsNormal('web/libraries/civicrm/core');
    $this->assertFileIsNormal('vendor/civicrm/civicrm-core/css/civicrm.css');
    $this->assertFileIsNormal('web/libraries/civicrm/core/css/civicrm.css');
    $this->assertSameFileContent('vendor/civicrm/civicrm-core/css/civicrm.css', 'web/libraries/civicrm/core/css/civicrm.css');
  }

  /**
   * When installing and uninstalling an example extension, ensure that the
   * public assets are updated in tandem.
   */
  public function testExt_RequireAndRemove() {
    $this->assertFileNotExists('vendor/civipkg/org.civicrm.api4/images/ApiExplorer.png');
    $this->assertFileNotExists('web/libraries/civicrm/org.civicrm.api4/images/ApiExplorer.png');

    PH::runOk('composer require civipkg/org.civicrm.api4');

    $this->assertFileExists('vendor/civipkg/org.civicrm.api4/images/ApiExplorer.png');
    $this->assertFileExists('web/libraries/civicrm/org.civicrm.api4/images/ApiExplorer.png');

    PH::runOk('composer remove civipkg/org.civicrm.api4');

    $this->assertFileNotExists('vendor/civipkg/org.civicrm.api4/images/ApiExplorer.png');
    $this->assertFileNotExists('web/libraries/civicrm/org.civicrm.api4/images/ApiExplorer.png');
  }

}

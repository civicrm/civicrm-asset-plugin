<?php
namespace Civi\AssetPlugin;

use ProcessHelper\ProcessHelper as PH;

class AssetPluginTestCase extends \PHPUnit\Framework\TestCase {

  /**
   * @return string
   *   The root folder of the civicrm-asset-plugin.
   */
  public static function getPluginSourceDir() {
    return dirname(__DIR__);
  }

  private static $origDir;
  private static $testDir;

  /**
   * Create a temp folder with a "composer.json" file and chdir() into it.
   *
   * @param array $composerJson
   * @return string
   */
  public static function initTestProject($composerJson) {
    self::$origDir = getcwd();
    self::$testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'assetplg-' . md5(__DIR__ . time() . rand(0, 10000));

    self::cleanDir(self::$testDir);
    mkdir(self::$testDir);
    file_put_contents(self::$testDir . DIRECTORY_SEPARATOR . 'composer.json', json_encode($composerJson, JSON_PRETTY_PRINT));
    chdir(self::$testDir);
    return self::$testDir;
  }

  public static function tearDownAfterClass() {
    parent::tearDownAfterClass();

    if (self::$testDir) {
      chdir(self::$origDir);
      self::$origDir = NULL;

      self::cleanDir(self::$testDir);
      self::$testDir = NULL;
    }
  }

  /**
   * If a directory exists, remove it.
   *
   * @param string $dir
   */
  private static function cleanDir($dir) {
    PH::runOk(['if [ -d @DIR ]; then rm -rf @DIR ; fi', 'DIR' => $dir]);
  }

}

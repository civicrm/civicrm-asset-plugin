<?php

namespace Civi\AssetPlugin;

use Civi\AssetPlugin\Util\GlobPlus;
use Composer\IO\IOInterface;

abstract class AbstractAssetRule implements AssetRuleInterface {

  /**
   * @var \Composer\Package\PackageInterface
   */
  protected $package;

  /**
   * @var string
   *   The name of the public folder for this package's assets.
   */
  protected $publicName;

  /**
   * @var string
   *   The full path to the original source for this package.
   */
  protected $srcPath;

  /**
   * ExtensionAssetRule constructor.
   *
   * @param \Composer\Package\PackageInterface $package
   * @param string $srcPath
   * @param string $publicName
   */
  public function __construct($package, $srcPath, $publicName) {
    $this->package = $package;
    $this->srcPath = $srcPath;
    $this->publicName = (string) $publicName;
  }

  public function publish(Publisher $publisher, IOInterface $io) {
    $localPath = $this->getLocalPath($publisher);
    // $webPath = $this->getWebPath($publisher);
    $globPatterns = $this->getIncludes($publisher);

    $io->write("DRY RUN: Map from {$this->srcPath} to {$localPath}");
    $io->write("         With: " . implode(', ', $globPatterns));

    $files = GlobPlus::find($this->srcPath, $globPatterns, $this->getExcludeDirs($publisher));
    foreach ($files as $file) {
      $io->write(" - $file");
    }
  }

  public function createAssetMap(Publisher $publisher, IOInterface $io) {
    return sprintf("\$GLOBALS['civicrm_asset_map'][%s][%s] = \$baseDir . %s;\n",
        var_export($this->getPackage()->getName(), 1),
        var_export('src', 1),
        var_export('/' . $this->srcPath, 1))
      . sprintf("\$GLOBALS['civicrm_asset_map'][%s][%s] = \$baseDir . %s;\n",
        var_export($this->getPackage()->getName(), 1),
        var_export('dest', 1),
        var_export('/' . $this->getLocalPath($publisher), 1))
      . sprintf("\$GLOBALS['civicrm_asset_map'][%s][%s] = %s;\n",
        var_export($this->getPackage()->getName(), 1),
        var_export('url', 1),
        var_export($this->getWebPath($publisher), 1));
  }

  /**
   * @return \Composer\Package\PackageInterface
   */
  public function getPackage() {
    return $this->package;
  }

  /**
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @return array
   *   Ex: ['css/*.css', '**.js']
   */
  public function getIncludes(Publisher $publisher) {
    return $publisher->getAssetConfig($this->publicName, 'include');
  }

  /**
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @return array
   *   Ex: ['.git', '.svn', '/CRM']
   */
  public function getExcludeDirs(Publisher $publisher) {
    return $publisher->getAssetConfig($this->publicName, 'exclude-dir');
  }

  /**
   * Get the local file-path to which we should write assets.
   *
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @return string
   */
  protected function getLocalPath(Publisher $publisher) {
    return $publisher->getLocalPath() . DIRECTORY_SEPARATOR . $this->publicName;
  }

  /**
   * Get the public URL path at which assets may be read.
   *
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @return string
   */
  protected function getWebPath(Publisher $publisher) {
    return $publisher->getWebPath() . '/' . $this->publicName;
  }

}

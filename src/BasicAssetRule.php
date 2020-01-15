<?php

namespace Civi\AssetPlugin;

use Composer\IO\IOInterface;

class BasicAssetRule extends AbstractAssetRule {

  protected $pathVar;

  /**
   * BasicAssetRule constructor.
   * @param \Composer\Package\PackageInterface $package
   * @param string $srcPath
   * @param string $publicName
   * @param string $pathVar
   */
  public function __construct($package, $srcPath, $publicName, $pathVar) {
    parent::__construct($package, $srcPath, $publicName);
    $this->pathVar = $pathVar;
  }

  /**
   * @param \Civi\AssetPlugin\Publisher $publisher
   * @param \Composer\IO\IOInterface $io
   * @return string
   *   PHP code with a list of asset-mapping statements.
   */
  public function createAssetMap(Publisher $publisher, IOInterface $io) {
    // The `$civicrm_path['civicrm.packages'] should point to the original folder b/c PHP files aren't mapped

    $withSlash = function($str) {
      return rtrim($str, '/' . DIRECTORY_SEPARATOR) . '/';
    };

    return parent::createAssetMap($publisher, $io)
    . sprintf("\$civicrm_paths[%s][%s] = %s;\n",
      var_export($this->pathVar, 1),
      var_export('path', 1),
      $this->exportPath($withSlash($this->srcPath)))
    . sprintf("\$civicrm_paths[%s][%s] = %s;\n",
      var_export($this->pathVar, 1),
      var_export('url', 1),
      // Common default: Relative to web root
      var_export($withSlash($this->getWebPath($publisher)), 1));
  }

}

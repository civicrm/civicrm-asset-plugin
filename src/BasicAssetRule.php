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
    return sprintf("\$civicrm_paths[%s][%s] = \$baseDir . %s;\n",
        var_export($this->pathVar, 1),
        var_export('path', 1),
        var_export('/' . $this->getLocalPath($publisher), 1))
      .
      sprintf("\$civicrm_paths[%s][%s] = %s;\n",
        var_export($this->pathVar, 1),
        var_export('url', 1),
        var_export('FIXME' . $this->getWebPath($publisher), 1));
  }

}

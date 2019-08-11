<?php

namespace Civi\AssetPlugin;

use Composer\IO\IOInterface;

class BasicAssetRule extends AbstractAssetRule {

  protected $pathVar;

  public function __construct(\Composer\Package\PackageInterface $package, $srcPath, $pathVar) {
    parent::__construct($package, $srcPath);
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
        var_export('/' . $publisher->createLocalPath($this->package), 1))
      .
      sprintf("\$civicrm_paths[%s][%s] = %s;\n",
        var_export($this->pathVar, 1),
        var_export('url', 1),
        var_export('/' . $publisher->createWebPath($this->package), 1));
  }

  public function publish(Publisher $publisher, IOInterface $io) {
    $io->write("TODO: BasicAssetRule::publish for " . $this->getPackage()
      ->getName());
  }

  /**
   * @return \Composer\Package\PackageInterface
   */
  public function getPackage() {
    return $this->package;
  }

}

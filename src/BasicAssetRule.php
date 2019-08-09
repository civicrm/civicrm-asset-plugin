<?php

namespace Civi\AssetPlugin;

use Composer\IO\IOInterface;

class BasicAssetRule implements AssetRuleInterface {

  /**
   * @var \Composer\Package\PackageInterface
   */
  protected $package;

  protected $pathVar;

  /**
   * BasicPublisher constructor.
   * @param \Composer\Package\PackageInterface $package
   */
  public function __construct(\Composer\Package\PackageInterface $package, $pathVar) {
    $this->package = $package;
    $this->pathVar = $pathVar;
  }

  public function createAutoloadSnippet(Publisher $publisher, IOInterface $io) {
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

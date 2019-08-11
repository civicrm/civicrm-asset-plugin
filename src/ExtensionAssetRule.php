<?php

namespace Civi\AssetPlugin;

use Composer\IO\IOInterface;

class ExtensionAssetRule extends AbstractAssetRule {

  public function createAssetMap(Publisher $publisher, IOInterface $io) {
    return "/* FIXME ExtensionAssetRule::createAutoloadSnippet */\n";
  }

  public function publish(Publisher $publisher, IOInterface $io) {
    $io->write("TODO: ExtensionAssetRule::publish for " . $this->getPackage()
      ->getName());
  }

  /**
   * @return \Composer\Package\PackageInterface
   */
  public function getPackage() {
    return $this->package;
  }

  //return [
  //'pkg' => $package->getName(),
  //'extKey' => "FIXME parse $infoXmlPath",
  //'src' => $package->getTargetDir(),
  //'dest' => $this->createLocalPath($package),
  //'ext' => Defaults::$defaultAssetExtensions,
  //];

}
